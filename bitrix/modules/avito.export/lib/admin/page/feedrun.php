<?php

namespace Avito\Export\Admin\Page;

use Avito\Export;
use Avito\Export\Concerns;
use Bitrix\Main;

class FeedRun extends Page
{
	protected $limits = [
		'element_limit' => 50,
		'time_limit' => 30,
		'time_sleep' => 3
	];

	use Concerns\HasLocale;

	public function hasRequest() : bool
	{
		return $this->request->isAjaxRequest() && $this->request->getPost('action') !== null;
	}

	public function processRequest() : void
	{
		try
		{
			$this->checkSession();
			$this->checkReadAccess();

			$action = $this->request->getPost('action');

			if ($action === 'run')
			{
				$response = $this->exportFeed();
			}
			else if ($action === 'stop')
			{
				$response = $this->clearFeed();
			}
			else
			{
				throw new Main\ArgumentException(sprintf('unknown %s action', $action));
			}

			Export\Utils\AjaxResponse::sendJson($response);
		}
		catch (Main\DB\SqlException $exception)
		{
			Export\Utils\AjaxResponse::sendJson($this->makeSystemExceptionResponse($exception));
		}
		catch (Main\SystemException $exception)
		{
			Export\Utils\AjaxResponse::sendJson($this->makeUserExceptionResponse($exception));
		}
		/** @noinspection PhpDuplicateCatchBodyInspection */
		catch (\Throwable $exception)
		{
			Export\Utils\AjaxResponse::sendJson($this->makeSystemExceptionResponse($exception));
		}
	}

	protected function makeUserExceptionResponse(\Throwable $exception) : array
	{
		$adminMessage = new \CAdminMessage(array(
			'TYPE' => 'ERROR',
			'MESSAGE' => $exception->getMessage(),
			'HTML' => true,
		));

		return [
			'status' => 'error',
			'message' => $adminMessage->Show(),
		];
	}

	protected function makeSystemExceptionResponse(\Throwable $exception) : array
	{
		$adminMessage = new \CAdminMessage(array(
			'TYPE' => 'ERROR',
			'MESSAGE' => self::getLocale('FATAL_ERROR'),
			'DETAILS' =>
				$exception->getMessage()
				. '<br />'
				. sprintf(
					'<textarea cols="90" rows="8">%s</textarea>',
					$exception->getTraceAsString()
				),
			'HTML' => true,
		));

		return [
			'status' => 'error',
			'message' => $adminMessage->Show(),
		];
	}

	protected function exportFeed() : array
	{
		$request = $this->request;
		$initTimestamp = $request->getPost('INIT_TIME');
		$initTime = $initTimestamp !== null
			? Main\Type\DateTime::createFromTimestamp($initTimestamp)
			: new Main\Type\DateTime();

		try
		{
			$feedId = (int)$request->getPost('ID');

			Export\Assert::notNull($feedId, '$request[ID]');

			$feed = Export\Feed\Setup\Model::getById($feedId);
			$timeLimit = (int)$request->getPost('TIME_LIMIT') ?: $this->limits['time_limit'];
			$timeSleep = (int)$request->getPost('TIME_SLEEP') ?: $this->limits['time_sleep'];
			$elementLimit = (int)$request->getPost('ELEMENT_LIMIT') ?: $this->limits['element_limit'];

			$controllerExport = new Export\Feed\Engine\Controller($feed, [
				'STEP' => $request->getPost('STEP'),
				'OFFSET' => $request->getPost('OFFSET'),
				'TIME_LIMIT' => $timeLimit,
				'INIT_TIME' => $initTime,
				'ELEMENT_LIMIT' => $elementLimit,
				'USE_TMP' => true,
			]);

			if ($request->getPost('STEP') === null)
			{
				$feed->pause();

				Export\Config::setOption('time_limit', $timeLimit);
				Export\Config::setOption('time_slip', $timeSleep);
				Export\Config::setOption('element_limit', $elementLimit);
			}

			session_write_close();

			$controllerExport->export();

			$feed->activate();

			if ($this->hasExportedOffers($feed))
			{
				$response = [
					'status' => 'ok',
					'message' =>
						$this->successMessage($feed)
						. $this->publishMessage(),
				];
			}
			else if ($this->hasFailedOffers($feed))
			{
				$response = [
					'status' => 'error',
					'message' => $this->failedOffersMessage($feed),
				];
			}
			else
			{
				$response = [
					'status' => 'error',
					'message' => $this->missingOffersMessage($feed),
				];
			}

			if ($feed->isRefreshTooLong())
			{
				$response['message'] .= PHP_EOL . $this->tooLongChangesMessage($feed);
			}

			$response['message'] .= PHP_EOL . $this->statisticMessage($feed);
			$response['message'] .= PHP_EOL . $this->logMessage($feed);
		}
		catch (Export\Watcher\Exception\LockFailed $exception)
		{
			$response = [
				'status' => 'progress',
				'message' => (new \CAdminMessage([
					'TYPE' => 'PROGRESS',
					'MESSAGE' => self::getLocale('FILE_LOCK'),
					'HTML' => true,
				]))->Show(),
				'state' => array_filter([
					'sessid' => bitrix_sessid(),
					'STEP' => $request->getPost('STEP'),
					'OFFSET' => $request->getPost('OFFSET'),
					'INIT_TIME' => $initTime->getTimestamp(),
				]),
			];
		}
		catch (Export\Watcher\Exception\TimeExpired $exception)
		{
			/** @var Export\Feed\Engine\Steps\Step $step */
			$step = $exception->getStep();
			$offset = $exception->getOffset();

			$response = [
				'status' => 'progress',
				'message' => (new \CAdminMessage([
					'TYPE' => 'PROGRESS',
					'MESSAGE' => $step->getTitle() . '...',
					'DETAILS' => $step->progressDetails($offset),
					'HTML' => true,
				]))->Show(),
				'state' => [
					'sessid' => bitrix_sessid(),
					'STEP' => $step->getName(),
					'OFFSET' => $offset,
					'INIT_TIME' => $initTime->getTimestamp(),
				],
			];
		}

		return $response;
	}

	protected function hasExportedOffers(Export\Feed\Setup\Model $feed) : bool
	{
		$query = Export\Feed\Engine\Steps\Offer\Table::getList([
			'filter' => [
				'=FEED_ID' => $feed->getId(),
				'=STATUS' => Export\Feed\Engine\Steps\Offer\Table::STATUS_OK,
			],
			'limit' => 1,
		]);

		return (bool)$query->fetch();
	}

	protected function hasFailedOffers(Export\Feed\Setup\Model $feed) : bool
	{
		$query = Export\Feed\Engine\Steps\Offer\Table::getList([
			'filter' => [
				'=FEED_ID' => $feed->getId(),
				'=STATUS' => Export\Feed\Engine\Steps\Offer\Table::STATUS_FAIL,
			],
			'limit' => 1,
		]);

		return (bool)$query->fetch();
	}

	protected function successMessage(Export\Feed\Setup\Model $feed) : string
	{
		$adminMessage = new \CAdminMessage(array(
			'MESSAGE' => self::getLocale('RUN_SUCCESS_TITLE'),
			'DETAILS' => self::getLocale('RUN_SUCCESS_DETAILS', [
				'#URL#' => $feed->getFileRelativePath(),
			]),
			'TYPE' => 'OK',
			'HTML' => true,
		));

		$result = $adminMessage->Show();
		$result .= sprintf(
			'<input type="text" size="40" value="%s">
			<button class="adm-btn js-export-copy_to_clipboard">%s</button>',
			htmlspecialcharsbx($feed->getUrl()),
			self::getLocale('RUN_SUCCESS_COPY')
		);

		return $result;
	}

	protected function publishMessage() : string
	{
		return BeginNote('style="max-width: 600px;"') . self::getLocale('NOTE_AUTO_UPLOAD') . EndNote();
	}

	protected function failedOffersMessage(Export\Feed\Setup\Model $feed) : string
	{
		$adminMessage = new \CAdminMessage(array(
			'MESSAGE' => self::getLocale('RUN_FAILED_OFFERS'),
			'DETAILS' => self::getLocale('RUN_FAILED_OFFERS_DETAILS', [
				'#LOG_URL#' => htmlspecialcharsbx($this->logUrl($feed)),
				'#EDIT_URL#' => htmlspecialcharsbx($this->editUrl($feed)),
			]),
			'TYPE' => 'ERROR',
			'HTML' => true,
		));

		return $adminMessage->Show();
	}

	protected function missingOffersMessage(Export\Feed\Setup\Model $feed) : string
	{
		$adminMessage = new \CAdminMessage(array(
			'MESSAGE' => self::getLocale('RUN_MISSING_OFFERS'),
			'DETAILS' => self::getLocale('RUN_MISSING_OFFERS_DETAILS', [
				'#EDIT_URL#' => htmlspecialcharsbx($this->editUrl($feed)),
			]),
			'TYPE' => 'ERROR',
			'HTML' => true,
		));

		return $adminMessage->Show();
	}

	protected function tooLongChangesMessage(Export\Feed\Setup\Model $feed) : string
	{
		if (!$feed->getAutoUpdate()) { return ''; }

		if ($feed->hasFullRefresh())
		{
			$result = BeginNote('style="max-width: 600px;"');
			$result .= self::getLocale('TOO_LONG_CHANGES_WARNING');
			$result .= EndNote();
		}
		else
		{
			$adminMessage = new \CAdminMessage(array(
				'MESSAGE' => self::getLocale('TOO_LONG_CHANGES_ERROR'),
				'DETAILS' => self::getLocale('TOO_LONG_CHANGES_ERROR_DETAILS', [
					'#EDIT_URL#' => htmlspecialcharsbx($this->editUrl($feed)),
				]),
				'TYPE' => 'ERROR',
				'HTML' => true,
			));

			$result = $adminMessage->Show();
		}

		return $result;
	}

	protected function statisticMessage(Export\Feed\Setup\Model $feed) : string
	{
		$partials = [];
		$statusMap = [
			'DONE' => Export\Feed\Engine\Steps\Offer\Table::STATUS_OK,
			'FAIL' => Export\Feed\Engine\Steps\Offer\Table::STATUS_FAIL,
		];

		foreach ($statusMap as $status => $value)
		{
			$count = Export\Feed\Engine\Steps\Offer\Table::getCount([
				'=FEED_ID' => $feed->getId(),
				'=STATUS' => $value,
			]);

			if ($count === 0) { continue; }

			$partials[] = sprintf(
				'<div>%s</div>',
				static::getLocale('STATISTIC_' . $status, [ '#COUNT#' => $count ])
			);
		}

		return implode('', $partials) . '<br />';
	}

	protected function logMessage(Export\Feed\Setup\Model $feed) : string
	{
		$result = '';

		$queryLog = Export\Logger\Table::getList([
			'filter' => [
				'=SETUP_TYPE' => Export\Glossary::SERVICE_FEED,
				'=SETUP_ID' => $feed->getId(),
			],
			'limit' => 1,
		]);

		if ($queryLog->fetch())
		{
			$result = self::getLocale('RUN_SUCCESS_LOG', [
				'#URL#' => htmlspecialcharsbx($this->logUrl($feed))
			]);
		}

		return $result;
	}

	protected function logUrl(Export\Feed\Setup\Model $feed) : string
	{
		return 'avito_export_log.php?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'find_setup_id' => Export\Glossary::SERVICE_FEED . ':' . $feed->getId(),
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);
	}

	protected function editUrl(Export\Feed\Setup\Model $feed) : string
	{
		return 'avito_export_feed_edit.php?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'id' => $feed->getId(),
		]);
	}

	protected function clearFeed() : array
	{
		$feedId = (int)$this->request->getPost('ID');

		Export\Assert::notNull($feedId, '$request[ID]');

		$feed = Export\Feed\Setup\Model::getById($feedId);
		$controller = new Export\Feed\Engine\Controller($feed);

		$controller->clear();

		return [
			'status' => 'ok',
		];
	}

	protected function getTabs(): \CAdminTabControl
	{
		$tabs = [
			[ 'DIV' => 'common', 'TAB' => self::getLocale('PARAMETERS') ]
		];

		return new \CAdminTabControl('AVITO_EXPORT_ADMIN_FEED_RUN', $tabs, true, true);
	}

	protected function getMenuContext(int $feedId): array
	{
		if ($feedId > 0)
		{
			$link = BX_ROOT . '/admin/avito_export_feed_edit.php?' . http_build_query([
				'lang' => LANGUAGE_ID,
				'id' => $feedId,
			]);
			$text = self::getLocale('FEED_EDIT');
		}
		else
		{
			$link = BX_ROOT . '/admin/avito_export_feeds.php?' . http_build_query([
				'lang' => LANGUAGE_ID,
			]);
			$text = self::getLocale('FEED_LIST');
		}

		return [
			[
				'ICON' => 'btn_list',
				'LINK' => $link,
				'TEXT' => $text,
			],
		];
	}

	public function setTitle() : void
	{
		global $APPLICATION;

		$APPLICATION->SetTitle(self::getLocale('TITLE'));
	}

	public function showForm() : void
	{
		global $APPLICATION;

		Main\UI\Extension::load('avitoexport.feed.runner');

		$tabControl = $this->getTabs();
		$requestedId = (int)$this->request->get('id');
		$contextMenu = $this->getMenuContext($requestedId);
		$formQuery = [
			'lang' => LANGUAGE_ID,
		];
		$formUrl = $APPLICATION->GetCurPageParam(http_build_query($formQuery), array_keys($formQuery));

		$context = new \CAdminContextMenu($contextMenu);
		$context->Show();
		?>
		<form action="<?= $formUrl ?>" method="post" class="avito-adm-form-run js-form">
			<div class="js-export-form__message"></div>
			<div style="display: none; padding: 10px 0" class="js-export-form__timer-holder">
				<?= self::getLocale('RUN_TIMER_TITLE')?><span class="js-export-form__timer">00:00</span>
			</div>
			<?php
			$tabControl->Begin();

			echo bitrix_sessid_post();

			$tabControl->BeginNextTab([ 'showTitle' => false ]);
			?>
			<tr>
				<td class="adm-detail-content-cell-l" width="40%"><?= self::getLocale('SETUP_ID')?></td>
				<td class="adm-detail-content-cell-r">
					<select name="ID">
						<?php
						foreach ($this->getFeeds() as $feed)
						{
							$isSelected = (int)$feed['ID'] === $requestedId ? 'selected' : '';
							$name = '[' . $feed['ID'] . '] ' . $feed['NAME'];

							/** @noinspection HtmlUnknownAttribute */
							echo sprintf('<option value="%s" %s>%s</option>', $feed['ID'], $isSelected, $name);
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l" width="40%">
					<?= self::getLocale('TIME_LIMIT')?>
				</td>
				<td class="adm-detail-content-cell-r">
					<input type="text" name="TIME_LIMIT" value="<?= Export\Config::getOption('time_limit', $this->limits['time_limit'])?>" size="2">
					<?= self::getLocale('TIME_SLEEP')?>
					<input type="text" name="TIME_SLEEP" value="<?= Export\Config::getOption('time_sleep', $this->limits['time_sleep'])?>" size="2">
					<?= self::getLocale('SEC')?>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l" width="40%"><?= self::getLocale('LIMIT_ELEMENTS')?></td>
				<td class="adm-detail-content-cell-r">
					<input type="text" name="ELEMENT_LIMIT" value="<?= Export\Config::getOption('element_limit', $this->limits['element_limit'])?>" size="4"> <?= self::getLocale('FACTOR_COUNT')?>
				</td>
			</tr>
			<?php
			$tabControl->Buttons();
			?>
			<input type="submit" class="adm-btn avito-btnform-run adm-btn-save js-export-form__run" value="<?= self::getLocale('BUTTON_RUN') ?>" />
			<input type="button" class="adm-btn js-export-form__stop" value="<?= self::getLocale('BUTTON_STOP') ?>" disabled />
			<?php
			$tabControl->End();
			?>
		</form>
		<script>
			BX.ready(function() {
				new BX.AvitoExport.Feed.Runner('.js-form', <?= Main\Web\Json::encode([
					'lang' => [
						'COPY_TO_BUFFER' => self::getLocale('RUN_COPY_TO_BUFFER'),
						'JS_ERROR' => self::getLocale('RUN_JS_ERROR'),
						'HTTP_ERROR' => self::getLocale('RUN_HTTP_ERROR'),
						'PARSE_ERROR' => self::getLocale('RUN_PARSE_ERROR'),
						'LINK_COPIED' => self::getLocale('LINK_COPIED'),
					],
					'errorTemplate' => (new \CAdminMessage([
						'TYPE' => 'ERROR',
						'MESSAGE' => '#TITLE#',
						'DETAILS' =>
							'#MESSAGE#'
							. '<br />'
							. '<textarea cols="90" rows="8">#DEBUG#</textarea>',
						'HTML' => true,
					]))->show(),
				]) ?>);
			});
		</script>
		<?php
	}

	protected function getFeeds() : array
	{
		$result = [];

		$query = Export\Feed\Setup\RepositoryTable::getList(['select' => ['ID', 'NAME']]);

		while ($feed = $query->fetch())
		{
			$result[$feed['ID']] = $feed;
		}

		return $result;
	}
}