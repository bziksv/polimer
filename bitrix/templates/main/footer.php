				<?if(!$is_main && $pages[1] !== 'basket' && !($pages[1] == 'catalog' && $pages[3])){?>
				</div><!--end::page_content-->
				<?}?>
				<footer>

					<div class="footer__top cl">
						<div class="footer__col col--1">
							<a href="/" class="footer__logo">
								<img src="<?=SITE_TEMPLATE_PATH?>/img/logo_svg.svg" alt="Полимер" width="206" height="44">
							</a>
							<p class="footer__logotext">Оптово-розничная торговля материалами и оборудованием для отопления и водоснабжения в Воронежской области.</p>
							<a href="/upload/politics.pdf" target="_blank" style="font-size: 11px; text-decoration: none; color: #4d4d4d;">Политика обработки персональных данных</a><br><a href="/upload/rules-recommendation.pdf" target="_blank" style="font-size: 11px; text-decoration: none; color: #4d4d4d;">Правила применения рекомендательных
технологий</a><br><a href="/upload/politika-ispolzovanija-cookies-polimer.pdf" target="_blank" style="font-size: 11px; text-decoration: none; color: #4d4d4d;">Политика использования cookie-файлов</a>
						</div><!--end::col__1-->

						<div class="footer__col col--2">
							<div class="footer__title">Магазин в Воронеже</div>

							<ul class="footer__list cl">
								<li><a href="tel:<?=tel(tplvar('phone_bottom_one'));?>" class="phone_engineer"><?=tplvar('phone_bottom_one', true);?></a></li>
								<li><a href="tel:<?=tel(tplvar('phone_bottom_two'));?>" class="phone_building"><?=tplvar('phone_bottom_two', true);?></a></li>
								<li><a href="/contacts/">Ильюшина, д.10Б</a></li>
							</ul>
							<p>Мы в социальных сетях:</p>
							<div style="display: flex; flex-wrap: nowrap; grid-gap: 10px;;">
								<div><noindex><a rel="nofollow" target="_blank" href="https://t.me/POLIMER36"><img width="35px" src="/upload/medialibrary/51f/1frjssl3sv9odyyf89clnppvq6jxl3ji.png" alt="telegram"></a></noindex></div>
								<div><noindex><a rel="nofollow" target="_blank" href="https://vk.com/polimer36"><img width="35px" src="/upload/medialibrary/d65/0gjxltunca3bxngbbnmkhhsojk19wemp.png" alt="vk"></a></noindex></div>
								<div><noindex><a rel="nofollow" target="_blank" href="https://max.ru/u/f9LHodD0cOLkMyinXju65cgGYgQ0l8C28ava4SefAFRoJGe0ylD6UW-JQSw"><img width="35px" src="/upload/medialibrary/786/qgqdkew0g27sd34tiplaqy2sye2n68nw.png" alt="max"></a></noindex></div>
							</div>
						</div><!--end::col__2-->
                        <div class="footer__col col--2">
                            <div class="footer__title">Магазин в Лисках</div>
                            <ul class="footer__list cl">
                                <li><a href="tel:+74739122082">+7 (47391) 220-82</a></li>
                                <li><a href="/contacts/">Проспект Ленина, 3</a></li>
                            </ul>
							<!--<p>Мы в социальных сетях:</p>
							<div style="display: flex; flex-wrap: nowrap; grid-gap: 10px;">
								<div><noindex><a rel="nofollow" target="_blank" href="https://t.me/POLIMER36"><img width="35px" src="/upload/medialibrary/51f/1frjssl3sv9odyyf89clnppvq6jxl3ji.png" alt="telegram"></a></noindex></div>
								<div><noindex><a rel="nofollow" target="_blank" href="https://vk.com/polimer36"><img width="35px" src="/upload/medialibrary/d65/0gjxltunca3bxngbbnmkhhsojk19wemp.png" alt="vk"></a></noindex></div>
							</div>-->
                        </div><!--end::col__2-->
                        <div class="footer__col col--2">
                            <div class="footer__title">Магазин в Старом Осколе</div>
                            <ul class="footer__list cl">
                                <li><a href="tel:+74725390911">+7 (4725) 39-09-11</a></li>
                                <li><a href="/contacts/">Проспект Алексея Угарова 18ж</a></li>
                            </ul>
							<!--<p>Мы в социальных сетях:</p>
							<div style="display: flex; flex-wrap: nowrap; grid-gap: 10px;">
								<div><noindex><a rel="nofollow" target="_blank" href="https://t.me/POLIMER36"><img width="35px" src="/upload/medialibrary/51f/1frjssl3sv9odyyf89clnppvq6jxl3ji.png" alt="telegram"></a></noindex></div>
								<div><noindex><a rel="nofollow" target="_blank" href="https://vk.com/polimer36"><img width="35px" src="/upload/medialibrary/d65/0gjxltunca3bxngbbnmkhhsojk19wemp.png" alt="vk"></a></noindex></div>
							</div>-->
                        </div><!--end::col__2-->

						<div class="footer__col col--2">
							<div class="footer__title">Каталог</div>
							<div class="cl">
								<ul class="footer__list footer__list--50">
									<li><a href="/catalog/inzhenernaya_santekhnika_otoplenie_vodoprovod_kanalizatsiya/">Инженерная сантехника</a></li>
									<li><a href="/catalog/stroitelnye_materialy/">Строительные материалы</a></li>
								</ul>
							</div>
							<?
							/*
							$APPLICATION->IncludeComponent(
								"bitrix:menu",
								"footer-catalog",
								array(
									"ALLOW_MULTI_SELECT" => "N",
									"CHILD_MENU_TYPE" => "footer",
									"DELAY" => "N",
									"MAX_LEVEL" => "1",
									"MENU_CACHE_GET_VARS" => array(
									),
									"MENU_CACHE_TIME" => "3600",
									"MENU_CACHE_TYPE" => "A",
									"MENU_CACHE_USE_GROUPS" => "Y",
									"ROOT_MENU_TYPE" => "footer_catalog",
									"USE_EXT" => "N",
									"COMPONENT_TEMPLATE" => "footer"
								),
								false
							); //footer__list */?>
						</div>
					</div>

					<div class="footer__bottom cl">

						<noindex><p style="font-size: .857em;">Наш сайт использует <a href="/upload/politika-ispolzovanija-cookies-polimer.pdf" target="_blank">cookies</a> для обеспечения работоспособности и сбора статистики. С их помощью мы анализируем пользовательскую активность, улучшаем работу сайта и делаем рекламу более релевантной. Оставаясь на сайте, вы даете согласие на обработку ваших персональных данных. Вы можете отключить сохранение cookies в настройках браузера в любой момент. На сайте также применяются <a href="/upload/rules-recommendation.pdf" target="_blank">рекомендательные технологии</a>. Подробнее об обработке персональных данных — в соответствующей <a href="/upload/politics.pdf" target="_blank">Политике</a>.</p></noindex>
						<div class="footer__copyright">© 2006 — <?=date("Y");?>. Полимер.</div>
						
						<div class="footer__studio">


						</div>
					</div>
				</footer>
			</div><!--end::wr-->
     	</div><!--end::container-->

    <?
    $config = \Bitrix\Main\Config\Configuration::getInstance()->get("exception_handling");
    if(!$config['debug']):
    ?>




    <? endif; ?>

<!-- remove submit type btn in request -->
<script>
(function(w, d, s, h, id) {
    w.roistatProjectId = id; w.roistatHost = h;
    var p = d.location.protocol == "https:" ? "https://" : "http://";
    var u = /^.*roistat_visit=[^;]+(.*)?$/.test(d.cookie) ? "/dist/module.js" : "/api/site/1.0/"+id+"/init";
    var js = d.createElement(s); js.charset="UTF-8"; js.async = 1; js.src = p+h+u; var js2 = d.getElementsByTagName(s)[0]; js2.parentNode.insertBefore(js, js2);
})(window, document, 'script', 'cloud.roistat.com', '0e03e67d2cf7ac55a00f173bca769e45');
</script>





<script>
        (function(w,d,u){
                var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
        })(window,document,'https://cdn-ru.bitrix24.ru/b7243579/crm/site_button/loader_3_co14nv.js');
</script>


	</body>
</html>
