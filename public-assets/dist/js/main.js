(()=>{"use strict";var t;(t=jQuery)(document).ready((function(){t(".variations_form").each((function(){t(this).on("found_variation",(function(a,i){var n;n=i.display_price,setTimeout((()=>{t("tamara-widget").attr("amount",n),window.TamaraWidgetV2.refresh()}),1e3)}))})),t("input[name=billing_phone]").change((function(){t("body").trigger("update_checkout")}))}))})();