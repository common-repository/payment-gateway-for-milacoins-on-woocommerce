jQuery(document).ready(function ($) {
  $("body").on("updated_checkout", function () {
    init();
  });
  $("form.checkout").on("submit", function (e) {
    e.preventDefault();
    var $form = $(this);
    var gateway = $("input[name=payment_method]:checked").val();
    if (gateway == "milacoins") {
      $form.addClass("processing").block({
        message: null,
        overlayCSS: {
          background: "#fff",
          opacity: 0.6,
        },
      });
      if ($("#milacoins-widget-con button").hasClass("custom")) {
        process_payment($form);
      }
    }
  });

  function process_payment(form) {
    $.ajax({
      type: "POST",
      url: JsmilacoinsData.checkoutUrl,
      data: form.serialize(),
      dataType: "json",
      success: function (result) {
        form.removeClass("processing").unblock();
        if ("failure" === result.result) {
          if (result.messages) {
            $(
              ".woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message"
            ).remove();
            form.prepend(
              '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' +
                result.messages +
                "</div>"
            );
            form
              .find(".input-text, select, input:checkbox")
              .trigger("validate")
              .blur();
            var scrollElement = $(
              ".woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout"
            );
            if (!scrollElement.length) {
              scrollElement = $(".form.checkout");
            }
            if (scrollElement.length) {
              $("html, body").animate(
                {
                  scrollTop: scrollElement.offset().top - 100,
                },
                1000
              );
            }
          }
        } else {
          if (result.redirect) {
            window.location = result.redirect;
          } else {
            run_milacoin(result.order_id);
            $("#milacoins-widget-con button").trigger("click");
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        throw_error(errorThrown, form);
      },
    });
  }

  function throw_error(errorThrown, form) {
    $(".woocommerce-NoticeGroup").remove();
    var message =
      '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><ul class="woocommerce-error" role="alert"><li>' +
      errorThrown +
      "</li></ul></div>";
    form.before(message);
    var scrollElement = $(
      ".woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout"
    );
    if (!scrollElement.length) {
      scrollElement = $(".form.checkout");
    }
    if (scrollElement.length) {
      $("html, body").animate(
        {
          scrollTop: scrollElement.offset().top - 100,
        },
        1000
      );
    }
  }

  function init() {
    var payment_radio = $("input[type=radio][name=payment_method]");
    payment_radio.change(function () {
      show_button(this.value);
    });

    function show_button(val) {
      if (val == "milacoins") {
        $("#milacoins-widget-con div").css('display', 'flex');
        $("#place_order").hide();
      } else {
        $("#milacoins-widget-con div").css('display', 'none');
        $("#place_order").show();
      }
    }
  }

  function run_milacoin(order_id) {
    var form = $("form.checkout");
    var key = JsmilacoinsData.key;
    milacoins.button({
      style: {
        width: "100%",
        height: "45px",
        color: JsmilacoinsData.color,
        buttonStyle: JsmilacoinsData.style,
      },
      widgetKey: key,
      source: "wooCommerce",
      elID: "milacoins-widget-con",
      amount: JsmilacoinsData.total,
      currency: JsmilacoinsData.currency,
      walletTarget: JsmilacoinsData.wallet,
      externalID: order_id,
      onError: (error) => {
        form.removeClass("processing").unblock();
        throw_error(error.message, form);
        console.log(error, "error");
      },
      onSuccess: (data) => {
        form.append(
          '<input type="hidden" name="invoice_id" value="' + data._id + '">'
        );
        process_payment(form);
        console.log(data, "invoice");
      },
      customer: {
        firstName: $("#billing_first_name").val(),
        lastName: $("#billing_last_name").val(),
        email: $("#billing_email").val(),
      },
      mode: JsmilacoinsData.mode,
    });
  }
});
