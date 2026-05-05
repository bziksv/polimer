<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("sale-dev");
?>

<style>

	.products_roll.sale {
		margin-bottom: 15px;
	}
	
	.products_roll .pr_box .item:nth-child(4n+1) {
		margin-left: 10px;
	}
	
	.products_roll .pr_box .item:nth-child(5n+1) {
		margin-left: 0px;
	}

	.products_roll.sale .pr_box .item .hover {
		background-image: url('https://universe-demo.ru/upload/resize_cache/iblock/d42/560_560_2/v4dsmoplxurh3zgdw5pakv5s5ty7s7ei.jpg');
		background-size: cover;
		background-repeat: no-repeat;
		background-position: center center;
		cursor:pointer;
	}
	
	.products_roll.sale .pr_box .item .hover .inner {
		padding: 20px 20px 0;
	}
	
	.products_roll.sale .pr_box .item .hover .discount {
		top: unset;
		bottom: unset;
		left: 20px;
	}
	
	.products_roll.sale .pr_box .item .hover .information {
		position: absolute;
		bottom: 10px;
		left: 15px;
		right: 15px;
		color: #FFF;
	}
	
	.products_roll.sale .pr_box .item .hover .information .duration {
		font-size: 12px;
		margin-bottom: 10px;
	}
	
	.products_roll.sale .pr_box .item .hover .information .name {
		font-size: 15px;
		font-weight: 500;
		line-height: 1.34;
	}
	
	.products_roll.sale .pr_box .item .hover::before {
		background: linear-gradient(180deg, rgba(0, 0, 0, 0) 35%, black 100%);
		opacity: 0.8;
	}
	
	.products_roll.sale .pr_box .item .hover::before {
		content: "";
		display: block;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		-webkit-transition-duration: 0.35s;
		-moz-transition-duration: 0.35s;
		-ms-transition-duration: 0.35s;
		-o-transition-duration: 0.35s;
		transition-duration: 0.35s;
		-webkit-transition-property: opacity;
		-moz-transition-property: opacity;
		-ms-transition-property: opacity;
		-o-transition-property: opacity;
		transition-property: opacity;
	}

</style>

<div class="h1">Акции</div>

<div class="products_roll sale" id="products-list">
			
	<div class="pr_box cl">
		<? for ($i = 0; $i < 40; $i++): ?>
		<a href="#" class="item">
			<div class="hover">
				<div class="inner">
					<div class="discount">- 5 %</div>
					<div class="information">
						<div class="duration">Срок акции с 01.10 по 31.12</div>
						<div class="name">Секция Стандарт Н2,03  L2,5   200х50х3,8мм  оц + ЭПП-500 RAL 6005 зеленый</div>
					</div>				
				</div>
			</div>
		</a>
		<? endfor; ?>
							
	</div>
	
</div>



<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>