<style>
    .advantages-section {
        width: 100%;
        padding: 80px 20px;
        background: #f5f7fa;
    }

    .advantages-container {
        max-width: 1300px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .advantages-title {
        text-align: center;
        font-size: 36px;
        color: #1a5f8f;
        margin-bottom: 60px;
        font-weight: 700;
        position: relative;
        padding-bottom: 20px;
    }

    .advantages-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, #2ea3f2 0%, #1a5f8f 100%);
        border-radius: 2px;
    }

    .advantages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
    }

    .advantage-card {
        background: white;
        border-radius: 12px;
        padding: 40px 30px;
        border-top: 4px solid transparent;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .advantage-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(46, 163, 242, 0.03) 0%, rgba(26, 95, 143, 0.03) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .advantage-card:hover {
        transform: translateY(-8px);
        border-top-color: #2ea3f2;
    }

    .advantage-card:hover::before {
        opacity: 1;
    }

    .advantage-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #2ea3f2 0%, #1a5f8f 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 25px;
        position: relative;
        z-index: 1;
    }

    .advantage-icon svg {
        width: 35px;
        height: 35px;
        fill: white;
    }

    .advantage-card-title {
        font-size: 22px;
        color: #1a5f8f;
        margin-bottom: 15px;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }

    .advantage-card-text {
        font-size: 15px;
        line-height: 1.7;
        color: #5a6b7c;
        position: relative;
        z-index: 1;
    }

    .advantage-card:nth-child(1) .advantage-icon {
        background: linear-gradient(135deg, #2ea3f2 0%, #1a5f8f 100%);
    }

    .advantage-card:nth-child(2) .advantage-icon {
        background: linear-gradient(135deg, #3ab0d4 0%, #1a5f8f 100%);
    }

    .advantage-card:nth-child(3) .advantage-icon {
        background: linear-gradient(135deg, #2ea3f2 0%, #1565a0 100%);
    }

    .advantage-card:nth-child(4) .advantage-icon {
        background: linear-gradient(135deg, #4db8e8 0%, #1a5f8f 100%);
    }

    .advantage-card:nth-child(5) .advantage-icon {
        background: linear-gradient(135deg, #2ea3f2 0%, #0d4f7a 100%);
    }

    @media (max-width: 768px) {
        .advantages-grid {
            grid-template-columns: 1fr;
        }
        
        .advantages-title {
            font-size: 28px;
        }
    }
</style>

<section class="advantages-section">
    <div class="advantages-container">
        <div class="advantages-grid">
            <!-- Преимущество 1 -->
            <div class="advantage-card">
                <div class="advantage-icon">
                    <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9H9V9h10v2zm-4 4H9v-2h6v2zm4-8H9V5h10v2z"/></svg>
                </div>
                <h3 class="advantage-card-title">Более 10 000 наименований товаров</h3>
                <p class="advantage-card-text">В каталоге всё необходимое для ваших задач. Экономьте время на поиске и деньги на конкурентных ценах.</p>
            </div>

            <!-- Преимущество 2 -->
            <div class="advantage-card">
                <div class="advantage-icon">
                    <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                </div>
                <h3 class="advantage-card-title">Опытные менеджеры-консультанты</h3>
                <p class="advantage-card-text">Эксперты помогут подобрать оптимальное решение. Наши специалисты регулярно проходят обучение и знают все нюансы рынка.</p>
            </div>

            <!-- Преимущество 3 -->
            <div class="advantage-card">
                <div class="advantage-icon">
                    <svg viewBox="0 0 24 24"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                </div>
                <h3 class="advantage-card-title">Быстрая доставка</h3>
                <p class="advantage-card-text">Отгружаем заказы в день обращения. Оперативно привозим товары по городу и области.</p>
            </div>

            <!-- Преимущество 4 -->
            <div class="advantage-card">
                <div class="advantage-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                </div>
                <h3 class="advantage-card-title">Работаем по всей России</h3>
                <p class="advantage-card-text">Отправляем грузы в любой регион через проверенные транспортные компании. Гарантия сохранности и чётких сроков.</p>
            </div>

            <!-- Преимущество 5 -->
            <div class="advantage-card">
                <div class="advantage-icon">
                    <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
                </div>
                <h3 class="advantage-card-title">Удобные подъездные пути</h3>
                <p class="advantage-card-text">Офисы и склады расположены у крупных магистралей. Забирайте заказы самовывозом без пробок и лишних задержек.</p>
            </div>
        </div>
    </div>
</section>


<!--<div class="at__block-four cl">
    <div class="item">
        <div class="title">
            БОЛЕЕ 10 000<br>
            НАИМЕНОВАНИЙ<br>
            ТОВАРОВ
        </div>
        <div class="txt">
            Ассортимент качественных товаров насчитывает более 10 000 наименований, что позволит Вам значительно сократить время на поиски, а гибкая ценовая политика — сэкономить деньги.
        </div>
    </div>
    <div class="item">
        <div class="title">
            ОПЫТНЫЕ<br>
            МЕНЕДЖЕРЫ<br>
            КОНСУЛЬТАНТЫ
        </div>
        <div class="txt">
            Разобраться во всем многообразии материалов и оборудования и сделать правильный выбор Вам помогут опытные менеджеры-консультанты, которые ежегодно участвуют в профессиональных тренингах и семинарах.
        </div>
    </div>
    <div class="item">
        <div class="title">
            ОТЛАЖЕННАЯ<br>
            СИСТЕМА<br>
            ЛОГИСТИКИ
        </div>
        <div class="txt">
            Отлаженная логистика позволяет доставлять Вам заказанные товары даже в день обращения. Доставка осуществляется в любую точку города и области, а также во все города России, где есть терминалы транспортных компаний.
        </div>
    </div>
    <div class="item">
        <div class="title">
            УДОБНЫЕ<br>
            ПОДЪЕЗДНЫЕ<br>
            ПУТИ
        </div>
        <div class="txt">
            Близость наших офисов к основным транспортным развязкам и магистралям, позволяют иногородним клиентам получать самовывозом заказанный товар без многочасовых простоев в городских пробках.
        </div>
    </div>
</div>
-->