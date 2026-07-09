<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

<div class="mp__blog" style="margin: 25px 0;">
	<div class="blog__header">
		<h2 style="margin: 0 auto;">Полезные статьи</h2>
    </div>

    <div class="blog__grid">
        <?foreach($arResult["ITEMS"] as $arItem):?>
        <div class="blog__card">
            <?if(!empty($arItem["PREVIEW_PICTURE"])):?>
                <a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="blog__image">
                    <img src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" 
                         alt="<?=$arItem["NAME"]?>" 
                         loading="lazy">
                </a>
            <?endif;?>

            <div class="blog__content">
                <a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="blog__title">
                    <?=$arItem["NAME"]?>
                </a>
                
                <?if($arItem["PREVIEW_TEXT"]):?>
                    <p class="blog__text">
                        <?=TruncateText($arItem["PREVIEW_TEXT"], 140)?>
                    </p>
                <?endif;?>
            </div>
        </div>
        <?endforeach;?>
    </div>
		<div style="text-align: center;"><a href="/articles/" class="blog__all-link">Читать больше статей →</a></div>
</div>

<style>
.blog__grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin: 32px 0;
}

.blog__card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    height: 100%;                    /* важно для выравнивания */
}

.blog__card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
}

.blog__image {
    display: block;
    position: relative;
    padding-top: 100%; /* квадрат */
}

.blog__image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Основное выравнивание карточек */
.blog__content {
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;                    /* растягиваем на всю высоту */
}

.blog__title {
    font-size: 1.05rem;
    line-height: 1.35;
    font-weight: 600;
    color: #0056b3;
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 3;           /* максимум 3 строки */
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex-grow: 0;                    /* не растягиваем заголовок */
}

.blog__text {
    font-size: 0.95rem;
    line-height: 1.5;
    color: #666;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-top: auto;                /* главное — прижимаем текст вниз */
}

/* Адаптив */
@media (max-width: 1024px) {
    .blog__grid { grid-template-columns: repeat(3, 1fr); gap: 20px; }
}

@media (max-width: 768px) {
    .blog__grid { grid-template-columns: repeat(2, 1fr); gap: 18px; }
}

@media (max-width: 576px) {
    .blog__grid { 
        grid-template-columns: 1fr; 
        gap: 16px; 
    }
    .blog__card { max-width: 380px; margin: 0 auto; }
}

.blog__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 10px;
}

.blog__all-link {
    color: #0056b3;
    font-weight: 500;
}
</style>