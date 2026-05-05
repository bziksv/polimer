<?php
namespace Avito\Export\Feed\Engine\Writer;

use Avito\Export;
use Bitrix\Main;
use Avito\Export\Utils;

class File
{
	use Export\Concerns\HasLocale;
	use Export\Concerns\HasOnce;

	public const BUFFER_LENGTH = 8192;
	public const BUFFER_LENGTH_BIG = 32768;

	public const POSITION_BEFORE = 'before';
	public const POSITION_PREPEND = 'prepend';
	public const POSITION_APPEND = 'append';
	public const POSITION_AFTER = 'after';

	private $file;
	private $fileOrigin;
	private $path;
	private $pathOrigin;
	private $fileResource;
	private $tmpResource;

	public function __construct(string $path, bool $useTmp)
	{
		if ($useTmp)
		{
			$this->pathOrigin = $path;
			$this->path = $path . '.tmp';
		}
		else
		{
			$this->path = $path;
		}
	}

	public function lock(bool $block = false) : bool
	{
		$fileResource = $this->getFileResource();

		return flock($fileResource, $block ? LOCK_EX : LOCK_EX | LOCK_NB);
	}

	public function unlock() : void
	{
		$fileResource = $this->getFileResource();

		flock($fileResource, LOCK_UN);
	}

	public function finalize(): void
	{
		$file = $this->getFile();
		$origin = $this->getFileOrigin();

		if ($origin === null) { return; }

		if ($origin->isExists())
		{
			$origin->delete();
		}

		$file->rename($origin->getPath());
	}

	public function copy() : void
	{
		$file = $this->getFile();
		$origin = $this->getFileOrigin();

		if ($origin === null) { return; }

		if ($file->isExists())
		{
			$file->delete();
		}

		$ready = copy($origin->getPath(), $file->getPath());

		if ($ready === false)
		{
			throw new Main\SystemException('cant copy tmp file');
		}
	}

	public function remove($isStrict = false) : void
	{
		$files = array_filter([
			$this->getFile(),
			$isStrict ? $this->getFileOrigin() : null,
		]);

		foreach ($files as $file)
		{
			$file->delete();
		}

		$this->fileResource = null;
	}

	public function getPointer() : int
	{
		$resource = $this->getFileResource();

		return (int)ftell($resource);
	}

	public function setPointer($position) : void
	{
		$resource = $this->getFileResource();

		fseek($resource, $position);
	}

	public function write($contents): void
	{
		$fileResource = $this->getFileResource();

		ftruncate($fileResource, 0);
		fseek($fileResource, 0);

		$this->fileWrite($fileResource, $contents);
	}

	private function getFileOrigin(): ?Main\IO\File
	{
		if ($this->fileOrigin === null && $this->pathOrigin !== null)
		{
			$this->fileOrigin = new Main\IO\File($this->pathOrigin);
		}

		return $this->fileOrigin;
	}

	private function getFile(): Main\IO\File
	{
		if ($this->file === null)
		{
			$this->file = new Main\IO\File($this->path);
		}

		return $this->file;
	}

	public function addTags($elementList, $tagParentName = 'offers', $position = null) : void
	{
		$isAfterSearch = false;

		switch ($position)
		{
			case static::POSITION_AFTER:
				$searchName = '</' . $tagParentName . '>';
				$isAfterSearch = true;
				break;

			case static::POSITION_BEFORE:
				$searchName = '<' . $tagParentName . '>';
				break;

			case static::POSITION_PREPEND:
				$searchName = '<' . $tagParentName . '>';
				$isAfterSearch = true;
				break;

			case static::POSITION_APPEND:
			default:
				$searchName = '</' . $tagParentName . '>';
				break;
		}

		$tagClosePosition = $this->getPosition($searchName);

		if ($tagClosePosition !== null)
		{
			if ($isAfterSearch)
			{
				$tagClosePosition += Utils\BinaryString::length($searchName);
			}

			$this->writeSplice($tagClosePosition, $tagClosePosition, implode($elementList));
		}
	}

	public function updateTags($elementList, $tagName = 'offer', $idAttr = 'Id', $isSelfClosed = null) : void
	{
		$searchList = [];

		foreach ($elementList as $id => $element)
		{
			$searchList[$id] = '<' . $tagName . '>' . PHP_EOL . '<' . $idAttr . '>' . $id . '</' . $idAttr . '>';
		}

		if (!empty($searchList))
		{
			$positionList = $this->getPositionList($searchList);

			$positionMap = array_flip($positionList);
			$selfClose = '/>';
			$selfCloseLength = Utils\BinaryString::length($selfClose);
			$tagClose = '</' . $tagName . '>';
			$tagCloseLength = Utils\BinaryString::length($tagClose);
			$waitMergePositions = [];
			$waitMergeContents = [];

			asort($positionList, SORT_NUMERIC);

			$positionElements = array_keys($positionList);

			foreach ($positionElements as $elementId)
			{
				$position = $positionList[$elementId];
				$closePosition = null;
				$selfClosePosition = ($isSelfClosed !== false ? $this->getPosition($selfClose, $position + 1, '<') : null); // stop next open tag

				if ($selfClosePosition !== null)
				{
					$closePosition = $selfClosePosition + $selfCloseLength;
				}
				else if ($isSelfClosed !== true)
				{
					$tagClosePosition = $this->getPosition($tagClose, $position + 1, '<' . $tagName . ' '); // stop on next tag same type

					if ($tagClosePosition !== null)
					{
						$closePosition = $tagClosePosition + $tagCloseLength;
					}
				}

				if ($closePosition === null)
				{
					continue;
				}

				/** @noinspection PhpIfWithCommonPartsInspection */
				if (isset($positionMap[$closePosition]))
				{
					$element = $elementList[$elementId];
					$mergeElementId = $positionMap[$closePosition];
					$mergePosition = $position;
					$mergeContents = $element;

					if (isset($waitMergePositions[$elementId]))
					{
						$mergePosition = $waitMergePositions[$elementId];
						$mergeContents = $waitMergeContents[$elementId] . $mergeContents;

						unset($waitMergePositions[$elementId], $waitMergeContents[$elementId]);
					}

					$waitMergePositions[$mergeElementId] = $mergePosition;
					$waitMergeContents[$mergeElementId] = $mergeContents;
				}
				else
				{
					$element = $elementList[$elementId];
					$newContents = $element;

					if (isset($waitMergePositions[$elementId]))
					{
						$position = $waitMergePositions[$elementId];
						$newContents = $waitMergeContents[$elementId] . $newContents;

						unset($waitMergePositions[$elementId], $waitMergeContents[$elementId]);
					}

					$diffLength = $this->writeSplice($position, $closePosition, $newContents);

					if ($diffLength !== 0)
					{
						foreach ($positionList as $nextElementId => $nextPosition)
						{
							if ($nextPosition > $position)
							{
								unset($positionMap[$nextPosition]);

								$newPosition = $nextPosition + $diffLength;

								$positionList[$nextElementId] = $newPosition;
								$positionMap[$newPosition] = $nextElementId;
							}
						}
					}
				}
			}
		}
	}

	/** @return resource */
	protected function getTmpResource()
	{
		if ($this->tmpResource !== null)
		{
			ftruncate($this->tmpResource, 0);
			fseek($this->tmpResource, 0);
		}
		else
		{
			$useMemory = ((string)Export\Config::getOption('export_writer_temp_memory', Export\Admin\UserField\BooleanType::VALUE_N) === Export\Admin\UserField\BooleanType::VALUE_Y);
			$tmpPath = $useMemory ? 'php://memory' : 'php://temp';
			$this->tmpResource = fopen($tmpPath, 'rb+');

			if ($this->tmpResource === false)
			{
				throw new Main\SystemException(self::getLocale('CANT_OPEN_TMP_BUFFER'));
			}
		}

		return $this->tmpResource;
	}

	protected function writeSplice($startPosition, $finishPosition, $contents = '')
	{
		$resource = $this->getFileResource();
		$tempResource = null;
		$contentsLength = Utils\BinaryString::length($contents);
		$diffLength  = $contentsLength - ($finishPosition - $startPosition);

		if ($diffLength !== 0) // copy contents after finish to temp
		{
			$tempResource = $this->getTmpResource();

			$this->streamCopy($resource, $tempResource, null, $finishPosition);
		}

		if ($diffLength < 0) // hanging end
		{
			ftruncate($resource, $startPosition);
		}

		fseek($resource, $startPosition);

		if ($contentsLength > 0) // write contents
		{
			$this->fileWrite($resource, $contents, $contentsLength);
		}

		if ($diffLength !== 0) // return contents after finish to initial resource
		{
			fseek($tempResource, 0);

			$this->streamCopy($tempResource, $resource);

			fseek($resource, $finishPosition); // restore resource position
		}

		return $diffLength;
	}

	protected function fileWrite($resource, $contents, $totalLength = null) : void
	{
		if ($totalLength === null)
		{
			$totalLength = Utils\BinaryString::length($contents);
		}

		$failCount = 0;
		$limitFail = 3;
		$readyLength = 0;

		do
		{
			$loopContents = $contents;

			if ($readyLength > 0)
			{
				$loopContents = Utils\BinaryString::substring($contents, $readyLength, $totalLength);
			}

			$loopLength = fwrite($resource, $loopContents);

			if ($loopLength === false)
			{
				throw new Main\SystemException(self::getLocale('FAIL_TO_WRITE_TO_FILE'));
			}

			if ($loopLength <= 0)
			{
				$failCount++;

				if ($failCount >= $limitFail)
				{
					throw new Main\SystemException(self::getLocale('FAIL_TO_WRITE_TO_FILE'));
				}
			}
			else
			{
				$readyLength += $loopLength;
			}
		}
		while ($readyLength < $totalLength);
	}

	protected function streamCopy($fromResource, $toResource, $maxLength = null, $offset = null) : void
	{
		if ($offset !== null)
		{
			if ($maxLength === null) { $maxLength = -1; }

			$copyResult = stream_copy_to_stream($fromResource, $toResource, $maxLength, $offset);
		}
		else if ($maxLength !== null)
		{
			$copyResult = stream_copy_to_stream($fromResource, $toResource, $maxLength);
		}
		else
		{
			$copyResult = stream_copy_to_stream($fromResource, $toResource);
		}

		if ($copyResult === false)
		{
			throw new Main\SystemException(self::getLocale('FAIL_TO_COPY_DATA_TO_TMP_BUFFER'));
		}
	}

	protected function getPositionList(array $searchList, $startPosition = null, $stopSearch = null) : array
	{
		$resource = $this->getFileResource();
		$isSupportReturnToStart = false;
		$bufferLength = $this->getBufferLength();

		if (!isset($startPosition))
		{
			$isSupportReturnToStart = true;
			$startPosition = ftell($resource);
		}
		else
		{
			fseek($resource, $startPosition);
		}

		$currentPosition = $startPosition;
		$bufferPosition = $currentPosition;
		$buffer = '';
		$isEndOfFileReached = false;
		$searchCount = count($searchList);
		$foundCount = 0;
		$isAllFound = false;
		$result = [];

		do
		{
			$iterationBuffer = fread($resource, $bufferLength);
			$buffer .= $iterationBuffer;

			foreach ($searchList as $searchKey => $searchVariant)
			{
				if (!isset($result[$searchKey]))
				{
					$variantPosition = Utils\BinaryString::position($buffer, $searchVariant);

					if ($variantPosition !== false)
					{
						$result[$searchKey] = $bufferPosition + $variantPosition;
						$foundCount++;

						$isAllFound = ($searchCount === $foundCount);
					}
				}
			}

			if ($stopSearch !== null)
			{
				$stopPosition = Utils\BinaryString::position($buffer, $stopSearch);

				if ($stopPosition !== false)
				{
					$stopPosition += $bufferPosition;

					foreach ($result as $searchKey => $position)
					{
						if ($position > $stopPosition)
						{
							unset($result[$searchKey]);
						}
					}

					break;
				}
			}

			if ($isAllFound)
			{
				break;
			}

			$buffer = $iterationBuffer;
			$bufferPosition = $currentPosition;
			$currentPosition += $bufferLength;

			if (!$isEndOfFileReached && feof($resource))
			{
				if ($isSupportReturnToStart)
				{
					$isEndOfFileReached = true;
					$bufferPosition = 0;
					$currentPosition = 0;
					$buffer = '';

					fseek($resource, 0);
				}
				else
				{
					break;
				}
			}
		}
		while(!$isEndOfFileReached || $currentPosition < $startPosition);

		return $result;
	}

	protected function getPosition($search, $startPosition = null, $stopSearch = null)
	{
		$searchList = [ 0 => $search ];
		$positionList = $this->getPositionList($searchList, $startPosition, $stopSearch);

		return $positionList[0] ?? null;
	}

	protected function read($startPosition, $finishPosition)
	{
		$resource = $this->getFileResource();

		fseek($resource, $startPosition);

		return fread($resource, $finishPosition - $startPosition);
	}

	private function getFileResource()
	{
		if ($this->fileResource === null)
		{
			$file = $this->getFile();

			if (!$file->isExists())
			{
				CheckDirPath($file->getPath());

				touch($file->getPath());
				chmod($file->getPath(), BX_FILE_PERMISSIONS);
			}
			else if (!$file->isWritable())
			{
				chmod($file->getPath(), BX_FILE_PERMISSIONS);
			}

			$this->fileResource = $file->open('rb+');
		}

		return $this->fileResource;
	}

	protected function getBufferLength() : int
	{
		return $this->once('getBufferLength', function() {
			if (filesize($this->path) > 10 ** 6) // more 1MB
			{
				return static::BUFFER_LENGTH_BIG;
			}

			return static::BUFFER_LENGTH;
		});
	}
}
