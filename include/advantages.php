<div class="advantages">
    <div class="advantages-item">
        <div class="advantages-icon"><img src="/bitrix/templates/main/img/advantage-20-years.svg" alt="" height="110"></div>
        <div class="advantages-title">Более 20 лет на рынке</div>
        <div class="advantages-description">Надёжный поставщик инженерных решений в регионе</div>
    </div>

    <div class="advantages-item">
        <div class="advantages-icon"><img src="/upload/medialibrary/bb0/991r9f1ihdhur6dmty3z6cxr98ns3808.png" alt="" height="110"></div>
        <div class="advantages-title">Более 10 000 товаров</div>
        <div class="advantages-description">В каталоге всё необходимое для ваших задач</div>
    </div>
    
    <div class="advantages-item">
        <div class="advantages-icon"><img src="/upload/medialibrary/f20/dez6es8zw0wka2lv6ncfkxq0pf4rpxw8.png" alt=""></div>
        <div class="advantages-title">Опытные менеджеры-консультанты</div>
        <div class="advantages-description">Эксперты помогут подобрать оптимальное решение</div>
    </div>
    
    <div class="advantages-item">
        <div class="advantages-icon"><img src="/upload/medialibrary/e60/9z6fp46gvzoudxxo92qd6d31empvb070.png" alt=""></div>
        <div class="advantages-title">Быстрая доставка</div>
        <div class="advantages-description">Отгружаем заказы в день обращения</div>
    </div>
    
    <div class="advantages-item">
        <div class="advantages-icon"><img src="/upload/medialibrary/055/uwoajvvz3e1b91nao19a5eofq8jrlyvh.png" alt=""></div>
        <div class="advantages-title">Работаем по всей России</div>
        <div class="advantages-description">Отправляем грузы в любой регион проверенными ТК</div>
    </div>
    
    <div class="advantages-item">
        <div class="advantages-icon"><img src="/upload/medialibrary/4ba/f81dorf18a1ll5102a5vy7iimbr3de6b.png" alt=""></div>
        <div class="advantages-title">Удобные подъездные пути</div>
        <div class="advantages-description">Офисы и склады расположены у крупных магистралей</div>
    </div>

    <div class="advantages-item">
        <div class="advantages-icon"><img src="/upload/medialibrary/3dd/47dohmnd035002zkgfgxti2ty9ftuecr.png" alt=""></div>
        <div class="advantages-title">Гарантия</div>
        <div class="advantages-description">Будьте уверены в качестве продукции</div>
    </div>

    <div class="advantages-item">
        <div class="advantages-icon"><img src="/upload/medialibrary/da1/xq8cwmkc6rnko37ncmtu7jir74uzhhno.png" alt=""></div>
        <div class="advantages-title">Скидки</div>
        <div class="advantages-description">Регулярно действуют специальные предложения</div>
    </div>
</div>

<style>
.advantages {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 32px;
    margin-bottom: 60px;
}

.advantages-item {
    background: #fff;
    border-radius: 16px;
    padding: 32px 24px;
    text-align: center;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid #f0f0f0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.advantages-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08),
                0 8px 10px -6px rgba(0, 0, 0, 0.08);
    border-color: #e5e7eb;
}

.advantages-icon {
    margin-bottom: 20px;
    transition: transform 0.4s ease;
}

.advantages-item:hover .advantages-icon {
    transform: scale(1.12) rotate(6deg);
}

.advantages-icon img {
    width: auto;
    height: 110px;
    max-width: 100%;
    object-fit: contain;
}

.advantages-title {
    font-size: 1.22em;
    font-weight: 700;
    line-height: 1.35;
    margin-bottom: 14px;
    color: #1f2937;
}

.advantages-description {
    font-size: 0.97em;
    line-height: 1.5;
    color: #64748b;
    flex-grow: 1;
}

/* Адаптив */
@media (max-width: 1100px) {
    .advantages {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 992px) {
    .advantages { gap: 28px; }
}

@media (max-width: 640px) {
    .advantages-item {
        padding: 28px 20px;
    }
}
</style>