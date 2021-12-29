jQuery( function ( $ ) {
  init_cecabank_meta();
  $(".cecabank_customize_cecabank_donations_field input:radio").on("change", function() {
    init_cecabank_meta();
  });

  function init_cecabank_meta(){
    if ("enabled" === $(".cecabank_customize_cecabank_donations_field input:radio:checked").val()){
      $(".cecabank_merchant").show();
      $(".cecabank_acquirer").show();
      $(".cecabank_secret_key").show();
      $(".cecabank_terminal").show();
      $(".cecabank_title").show();
      $(".cecabank_description").show();
      $(".cecabank_environment").show();
    } else {
      $(".cecabank_merchant").hide();
      $(".cecabank_acquirer").hide();
      $(".cecabank_secret_key").hide();
      $(".cecabank_terminal").hide();
      $(".cecabank_title").hide();
      $(".cecabank_description").hide();
      $(".cecabank_environment").hide();
    }
  }
});