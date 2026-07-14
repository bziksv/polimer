<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "Яндекс Сплит» — это современный сервис, который позволяет совершать покупки сейчас, а платить за них частями. Его главная задача — сделать крупные и не очень траты более комфортными для вашего бюджета");
$APPLICATION->SetTitle("О системе \"Яндекс сплит\"");
?>
<div style="padding-top: 15px;">
	<img style="float: right; width: 100px; border-radius: 50%; box-shadow: rgba(0, 0, 0, 0.2) 0px 2px 5px; margin-left: 20px" src="/upload/medialibrary/7f3/k9k55689rvvuwegvrcapk91rbmgn588w.jpg">
	<h1 style="padding-top: 15px;">Оплачивайте частями с Яндекс сплит</h1>
	<p>Яндекс Сплит» — это современный сервис, который позволяет совершать покупки сейчас, а платить за них частями. Его главная задача — сделать крупные и не очень траты более комфортными для вашего бюджета.</p>
    <style>
        .steps-wrapper {
            padding: 5px 50px 25px 50px;
            font-family: Arial, sans-serif;
        }

        .steps-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
        }

        .step-block {
            width: 200px;
            height: 200px;
            background-color: #f9f9f9;
            border-radius: 20px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .step-number {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #8b8b8b;
        }

        .step-text {
            text-align: center;
            color: #333;
            font-size: 16px;
            padding: 20px;
        }

        .step-connector {
            flex: 0 0 80px;
            height: 2px;
            background-image: linear-gradient(to right, #b8b8b8 50%, transparent 50%);
            background-size: 10px 2px;
            background-repeat: repeat-x;
        }
    </style>

    <div class="steps-wrapper">
        <div class="steps-container">
            <div class="step-block">
                <span class="step-number">1</span>
				<span class="step-text"><em>При оформлении заказа выберите "Оплата картой" в способах оплаты.</em></span>
            </div>
	<div class="step-connector"></div>
            <div class="step-block">
                <span class="step-number">2</span>
				<span class="step-text"><em>Выберите "Яндекс сплит"</em></span>
            </div>
            <div class="step-connector"></div>
            <div class="step-block">
                <span class="step-number">3</span>
                <span class="step-text"><em>Оплатите первую часть, и заберите покупку.</em></span>
            </div>
            <div class="step-connector"></div>
            <div class="step-block">
                <span class="step-number">4</span>
                <span class="step-text"><em>Остальные платежи будут списаны позже по графику.</em></span>
            </div>
        </div>
    </div>

	<p> Это не кредит в классическом понимании. «Яндекс Сплит» работает по модели «покупай сейчас, плати потом». Это означает, что вы получаете товар сразу после внесения первого платежа, а остаток суммы выплачиваете равными долями по установленному графику. </p>



<style>
	.advantages-main {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-around;
	}

	.advantage-item {
		display: flex;
		width: 45%;
		border: 2px solid #3187e0;
		border-radius: 15px;
		padding: 20px;
		margin-bottom: 25px;
		gap: 15px;
	}

	.advantage-title {
		font-size: 18px;
		font-weight: 700;
	}

	.advantage-icon {
		width: 30%;
	}
</style>

<div class="advantages-main">

<div class="advantage-item">
	<div class="advantage-icon"><img src="/bitrix/templates/main/img/percent-solid-full.png"></div>
	<div class="advantage-description">
		<div class="advantage-title">Беспроцентная рассрочка</div>
		<div class="advantage-text">При оплате частями на 2 месяца — 0% переплаты. Вы платите ровно стоимость товара без скрытых комиссий.</div>
</div>
</div>


<div class="advantage-item">
	<div class="advantage-icon"><img src="/bitrix/templates/main/img/file-solid-full.png"></div>
	<div class="advantage-description">
		<div class="advantage-title">Без лишних документов</div>
		<div class="advantage-text">Не нужны паспорт, справки о доходах или кредитная история. Оформление за 10 секунд через Яндекс ID.</div>
</div>
</div>

<div class="advantage-item">
	<div class="advantage-icon"><img src="/bitrix/templates/main/img/coins-solid-full.png"></div>
	<div class="advantage-description">
		<div class="advantage-title">Досрочное погашение без штрафов</div>
		<div class="advantage-text">Закрыть остаток можно в любой момент — полностью или частично. Никаких комиссий за досрочку.</div>
</div>
</div>

<div class="advantage-item">
	<div class="advantage-icon"><img src="/bitrix/templates/main/img/ban-solid-full.png"></div>
	<div class="advantage-description">
		<div class="advantage-title">Не влияет на кредитную историю</div>
		<div class="advantage-text">Обычный «Сплит» — не кредит, данные не передаются в БКИ. Можно пользоваться без риска для будущих займов.</div>
</div>
</div>
</div>




	<h2 style="text-align: center;">Несколько разновидностей сплита</h2>
	<p style="text-align: center;">Вы можете выбрать сплит под покупку, у них разные лимиты, сроки и условия.</p>
<style>
  .compare-table {
    width: 100%;
    border-collapse: collapse;
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    font-size: 14px;
    line-height: 1.4;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
	margin-bottom: 30px;
  }
  .compare-table th {
    background: #f5f7fa;
    color: #1e2a3a;
    font-weight: 600;
    padding: 14px 12px;
    text-align: left;
    border-bottom: 1px solid #e2e6ea;
    font-size: 15px;
  }
  .compare-table td {
    padding: 12px;
    border-bottom: 1px solid #eef2f5;
    vertical-align: top;
    background-color: #fff;
    color: #2c3e4e;
  }
  .compare-table tr:last-child td {
    border-bottom: none;
  }
  .compare-table .feature {
    font-weight: 600;
    background-color: #fafbfc;
    width: 160px;
  }
  .badge {
    display: inline-block;
    background: #f0f2f5;
    border-radius: 20px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: 500;
    margin-top: 4px;
  }
  .free {
    color: #00a340;
    font-weight: 600;
  }
  .highlight {
    background: #fff0e8;
    border-radius: 12px;
    padding: 2px 6px;
    font-size: 12px;
    display: inline-block;
  }
</style>

<table class="compare-table">
  <thead>
    <tr>
      <th>Характеристика</th>
      <th>Сплит</th>
      <th>Супер Сплит</th>
      <th>Сплит для бизнеса</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td class="feature">Максимальная сумма</td>
      <td>до 150 000 ₽</td>
      <td>до 1 000 000 ₽</td>
      <td>— (зависит от оборота, лимиты по транзакции до 1 млн ₽)</td>
    </tr>
    <tr>
      <td class="feature">Срок рассрочки / кредита</td>
      <td>2, 4 или 6 месяцев</td>
      <td>от 2 до 24 месяцев</td>
      <td>моментальное зачисление продавцу (покупатель платит частями)</td>
    </tr>
    <tr>
      <td class="feature">Комиссия / ставка</td>
      <td><span class="free">2 мес — 0%</span>, 4–6 мес — от 5%</td>
      <td>от 10% годовых после льготного периода</td>
      <td>комиссия за транзакцию (от 2,5% / по тарифу Яндекс Кассы)</td>
    </tr>
    <tr>
      <td class="feature">Документы и проверка</td>
      <td>только номер телефона, Яндекс ID</td>
      <td>паспорт, Госуслуги, проверка кредитной истории</td>
      <td>ИНН, ОКВЭД, регистрация юрлица / ИП</td>
    </tr>
    <tr>
      <td class="feature">Влияние на кредитную историю</td>
      <td>❌ Нет (не кредит)</td>
      <td>✅ Да (кредитный договор)</td>
      <td>не относится (для бизнеса)</td>
    </tr>
    <tr>
      <td class="feature">Где работает</td>
      <td>только у партнёров Яндекса и в сервисах Яндекса</td>
      <td>везде, как обычная карта МИР</td>
      <td>интернет-магазин, офлайн по QR, API интеграция</td>
    </tr>
    <tr>
      <td class="feature">Особенности</td>
      <td>мгновенное оформление, досрочное погашение без штрафов</td>
      <td>цифровая/пластиковая карта, платежи частями для крупных покупок</td>
      <td>деньги продавцу сразу, все риски на Яндексе, рост среднего чека</td>
    </tr>
  </tbody>
</table>







 <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Основной контейнер блока FAQ */
        .faq-section {
            max-width: 860px;
            width: 100%;
            background: #ffffff;
            border-radius: 32px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.03), 0 2px 6px rgba(0, 0, 0, 0.05);
            padding: 32px 28px;
        }

        /* Заголовок */
        .faq-title {
            margin-bottom: 8px;
        }

        .faq-sub {
            font-size: 16px;
            color: #5a6e7c;
            border-left: 3px solid #fc3f1d;
            padding-left: 14px;
            margin-bottom: 32px;
            font-weight: 400;
        }

        /* Сетка вопросов */
        .faq-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
		 margin-bottom: 25px;
        }

        /* Карточка вопроса */
        .faq-item {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #eaedf0;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .faq-item:hover {
            border-color: #d0d5db;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.02);
        }

        /* Вопрос — кликабельная область (аккордеон) */
        .faq-question {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px 24px;
            cursor: pointer;
            background: #fff;
            transition: background 0.2s;
            user-select: none;
        }

        .faq-question:hover {
            background-color: #fafbfc;
        }

        /* Иконка-галочка (стиль как на вашем скрине) */
        .check-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            background-color: #00b83b;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* Или можно SVG-галочку */
        .check-icon::after {
            content: "✓";
            font-weight: 600;
        }

        .question-text {
            font-size: 18px;
            font-weight: 500;
            color: #1e2a3a;
            flex: 1;
            line-height: 1.3;
        }

        /* Стрелка поворота */
        .arrow-icon {
            font-size: 20px;
            color: #8f9eab;
            transition: transform 0.25s ease;
            flex-shrink: 0;
        }

        /* Ответ (скрыт по умолчанию) */
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fefefe;
            border-top: 1px solid transparent;
        }

        .faq-answer-inner {
            padding: 15px 24px 20px 62px; /* отступ под иконку + вопрос */
            color: #2c3e4e;
            font-size: 15px;
            line-height: 1.5;
        }

        /* Активный (открытый) блок */
        .faq-item.active .faq-answer {
            max-height: 300px; /* достаточно для ответа */
            border-top-color: #eaedf0;
        }

        .faq-item.active .arrow-icon {
            transform: rotate(180deg);
        }

        /* Дополнительные элементы в ответах (списки, выделения) */
        .highlight {
            background: #fff0e8;
            padding: 2px 6px;
            border-radius: 12px;
            font-weight: 500;
            color: #e25c2c;
            font-size: 14px;
        }

        .small-note {
            font-size: 13px;
            color: #6c7f8f;
            margin-top: 8px;
            display: inline-block;
        }

        hr {
            margin: 12px 0;
            border: none;
            border-top: 1px solid #eef2f5;
        }

        /* Адаптивность */
        @media (max-width: 640px) {
            .faq-section {
                padding: 20px 16px;
            }
            .faq-title {
                font-size: 24px;
            }
            .question-text {
                font-size: 16px;
            }
            .faq-answer-inner {
                padding-left: 48px;
                padding-right: 16px;
            }
            .faq-question {
                padding: 16px 18px;
            }
        }
    </style>


	<h2 style="text-align: center; margin-bottom: 10px;">Часто спрашивают о Сплите</h2>

    <div class="faq-grid" id="faqContainer">
        <!-- Вопрос 1 -->
        <div class="faq-item">
            <div class="faq-question">
                <span class="check-icon"></span>
                <span class="question-text">Можно ли досрочно погасить Сплит?</span>
                <span class="arrow-icon">▼</span>
            </div>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    Да, вы можете погасить задолженность досрочно в любой момент — полностью или частично. 
                    <strong>Никаких комиссий и штрафов</strong> за досрочное погашение нет. 
                    Просто зайдите в раздел «Сплит» в приложении Яндекса → выберите покупку → нажмите «Погасить досрочно».
                    <div class="small-note">При частичном досрочном погашении уменьшается сумма следующих платежей.</div>
                </div>
            </div>
        </div>

        <!-- Вопрос 2 -->
        <div class="faq-item">
            <div class="faq-question">
                <span class="check-icon"></span>
                <span class="question-text">Что будет, если просрочить платёж по Сплиту?</span>
                <span class="arrow-icon">▼</span>
            </div>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    За каждый день просрочки начисляется <span class="highlight">пеня 0,1% от суммы неоплаченного платежа</span>. 
                    Если вовремя не оплатить, доступ к сервису «Сплит» временно блокируется, и информация может передаваться в бюро кредитных историй (для «Супер Сплита» — обязательно). 
                    <br>Важно: Яндекс пришлёт push-уведомление и письмо за 3 дня до списания — не игнорируйте их.
                </div>
            </div>
        </div>

        <!-- Вопрос 3 -->
        <div class="faq-item">
            <div class="faq-question">
                <span class="check-icon"></span>
                <span class="question-text">Как увеличить доступную сумму для сплитования?</span>
                <span class="arrow-icon">▼</span>
            </div>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    Лимит динамический и зависит от:
                    <ul style="margin: 8px 0 0 20px; color: #2c3e4e;">
                        <li>активности в сервисах Яндекса (Такси, Еда, Лавка, Маркет),</li>
                        <li>своевременности оплаты предыдущих сплитов,</li>
                        <li>наличия подтверждённого номера и стажа использования Яндекс ID.</li>
                    </ul>
                    <hr>
                    <strong>Совет:</strong> совершайте небольшие покупки с «Сплитом» и платите без задержек — лимит будет расти. Для увеличения до 1 000 000 ₽ оформите «Супер Сплит» с проверкой через Госуслуги.
                </div>
            </div>
        </div>

        <!-- Вопрос 4 -->
        <div class="faq-item">
            <div class="faq-question">
                <span class="check-icon"></span>
                <span class="question-text">Работает ли Сплит за границей или в иностранных магазинах?</span>
                <span class="arrow-icon">▼</span>
            </div>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    Обычный «Сплит» доступен только у партнёров Яндекса внутри РФ. 
                    Но <strong>«Супер Сплит»</strong> (цифровая карта) работает везде, где принимают карты МИР — в том числе за границей (в Турции, ОАЭ, странах СНГ и др.). 
                    Однако учтите: конвертация валюты происходит по курсу платёжной системы, и комиссия может быть выше.
                </div>
            </div>
        </div>

        <!-- Вопрос 5 -->
        <div class="faq-item">
            <div class="faq-question">
                <span class="check-icon"></span>
                <span class="question-text">Можно ли объединить несколько сплитов в один платёж?</span>
                <span class="arrow-icon">▼</span>
            </div>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    Нет, технически каждый заказ — отдельный график платежей. Но в приложении есть <strong>единая сводка</strong>, где видны все предстоящие списания. 
                    Вы можете настроить автоплатёж, чтобы средства списывались с карты в нужные даты по каждой покупке автоматически. 
                    <div class="small-note">Также можно перенести дату платежа (доступно в настройках конкретного сплита).</div>
                </div>
            </div>
        </div>

        <!-- Вопрос 6 -->
        <div class="faq-item">
            <div class="faq-question">
                <span class="check-icon"></span>
                <span class="question-text">Влияет ли Сплит на кредитную историю?</span>
                <span class="arrow-icon">▼</span>
            </div>
            <div class="faq-answer">
                <div class="faq-answer-inner">
                    <strong>Обычный «Сплит» (BNPL)</strong> — нет, он не передаёт данные в БКИ. Это просто рассрочка внутри экосистемы Яндекса.<br>
                    <strong>«Супер Сплит»</strong> — да, это полноценный потребительский кредит, и информация о ваших платежах (в том числе просрочках) попадает в кредитные истории.
                    <br><br>Вывод: для простых мелких покупок можете не волноваться, а для крупных — будьте внимательны.
                </div>
            </div>
        </div>
    </div>

<script>
    // Добавляем интерактивность (аккордеон)
    const items = document.querySelectorAll('.faq-item');
    items.forEach(item => {
        const questionDiv = item.querySelector('.faq-question');
        questionDiv.addEventListener('click', () => {
            // Закрываем остальные (опционально, можно убрать если хотите несколько открытых)
            // items.forEach(other => {
            //     if (other !== item && other.classList.contains('active')) {
            //         other.classList.remove('active');
            //     }
            // });
            item.classList.toggle('active');
        });
    });

    // По умолчанию первый вопрос открыт (для наглядности, но не обязательно)
    if(items.length) {
        // items[0].classList.add('active'); // раскомментировать если хотите открытый по умолчанию
    }
</script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>