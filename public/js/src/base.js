
$("#step1Submit").on( "click", function() {

    var daRequest = $('#daRequest').val();
    var radioCheck = $('input[name=stepOneRequest]:checked', '#step1').val();

    if ( radioCheck == 'nameRequest') {
        var url = 'http://192.168.1.100/skroutzbot/public/api/search?name='+daRequest
    } else if (radioCheck == 'urlRequestRadio') {
        var url = 'http://192.168.1.100/skroutzbot/public/api/search?url='+daRequest
    }

    console.log(url);
    if (url != null) {
        $.ajax({url: url, success: function(result){

                $("#productsTable tr").remove();
                $("#productImage").attr("src","");
                $("#lowestPrice").html("");
                $("#productTable tr").remove();

                console.log('result',result);
                if (result.active > 0) {
                    // console.log(result.active);
                    var products = result.products;
                    console.log(products);

                    $.each(products, function( key, value ) {
                        $("#productsTable").append("<tr><td><input type='radio' name='productRadio' id='s-"+value.uuid+"' value='" + value.uuid + "'></td><td><label for='s-"+value.uuid+"'>"+ value.Name +"</label></td><td><a href='" + value.Url + "' target='_blank'>URL</a></td></tr>");
                    })
                    $("#step1").removeClass("active");
                    $("#step2").addClass("active");

                }
                    // else if (result.active === 1) {
                    //
                    //     var productOneResult = result.products[0].url;
                    //     console.log(productOneResult);
                    //     // $.ajax({url: productOneResult, success: function(productInstaResult){
                    //     //     console.log('result',productInstaResult);
                    //     //     $("#productImage").attr("src",productInstaResult.data.photo);
                    //     //     $("#lowestPrice").html("Lowest Price: " + productInstaResult.products[0].price);
                    //     // }});
                    //
                // }
                else {
                    console.log('no results');
                }
            }});
    } else {
        alert('Check url or name');
    }


});

var productId;

$("#step2Submit").on( "click", function() {
    var radioProductCheck = $('input[name=productRadio]:checked', '#step2').val();
    var fetchURL = 'http://192.168.1.100/skroutzbot/public/api/fetch?ids=' + radioProductCheck;
    console.log(fetchURL);
    $("#productTable tr").remove();
    $.ajax({url: fetchURL, success: function(productResult){
            console.log('result',productResult);
            productId = productResult.productData.uuid;
            $("#productImage").attr("src",productResult.productData.photo);
            $("#productName").html(productResult.productData.name);
            $("#lowestPrice").html("Χαμηλότερη τιμή αυτή την στιγμή: " + productResult.prices[0].price.NetPrice +"€");
            $.each(productResult.prices, function( key, value ) {
                $("#productTable").append(
                    "<tr style='color: rgb(55, 54, 54);'>" +
                    "<td><input type='checkbox' name='productList' id='p-"+ value.shop.uuid +"' value='"+ value.shop.uuid +"' disabled></td>" +
                    "<td><label for='p-"+ value.shop.uuid +"'>" + value.shop.name + "</label></td>" +
                    "<td>" + value.price.NetPrice +"€" + "</td>" +
                    "</tr>"
                );
            });
            var productID = productResult.productData.uuid;
            $("#step2").removeClass("active");
            $("#step3").addClass("active");
        }});
})


$(".step3Submit").on( "click", function() {
    $("#step3").removeClass("active");
    $("#step4").addClass("active");
})

$("#step4Submit").on( "click", function() {

    var shopsSelected = new Array();
    var shopsRadio = $('input[name=shopsRadio]:checked', '#step3').val();

    var increment = $('#posostieaPrice').val();
    var lowestPrice = $('#elaxistiPrice').val();

    if (shopsRadio != null) {

        if (shopsRadio=="shopSelected") {
            $.each($("input[name='productList']:checked"), function(){
                shopsSelected.push($(this).val());
            });
        }

        $.ajax({
            url: 'http://192.168.1.100/skroutzbot/public/api/save',
            type: "POST",
            data: {
                'mode':shopsRadio,
                'competitors':shopsSelected,
                'increment':increment,
                'lowestPrice':lowestPrice,
                'productId':productId,

            }
        }).done(function() {
            console.log('Sended');
        });

    } else {
        alert('Check Χαμηλότερη or Χαμηλότερη από ανταγωνιστές');
    }



});

$("form#uploadForm").submit(function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'http://192.168.1.100/skroutzbot/public/user-profile/save',
        type: 'POST',
        data: formData,
        success: function (data) {

            window.location.href = 'http://192.168.1.100/skroutzbot/public/user-profile';
        },
        cache: false,
        contentType: false,
        processData: false
    });
});


$("#step2Back").on( "click", function() {
    $("#step2").removeClass("active");
    $("#step1").addClass("active");
});

$(".step3Back").on( "click", function() {
    $("#step3").removeClass("active");
    $("#step2").addClass("active");
});

$("#step4Back").on( "click", function() {
    $("#step4").removeClass("active");
    $("#step3").addClass("active");
});

$('input:radio[name="stepOneRequest"]').on("change",function(){
    $(this).parent().css('border','1px solid #F68B24')

    if ($(this).val() == 'nameRequest') {
        $('#urlRequestRadio').parent().css('border','0')
    }
    else if ($(this).val() == 'urlRequestRadio') {
        $('#nameRequestRadio').parent().css('border','0')
    }
});


$('input:radio[name="shopsRadio"]').on("change",function(){
    $(this).parent().css('border','1px solid #F68B24')

    if ($(this).val() == 'shopFirst') {
        $('#shopSelected').parent().css('border','0')
        $('input:checkbox[name="productList"]').prop('disabled', true);
        $('input:checkbox[name="productList"]').parent().parent().css('color','#373636')
    }
    else if ($(this).val() == 'shopSelected') {
        $('#shopFirst').parent().css('border','0')
        $('input:checkbox[name="productList"]').prop('disabled', false);
        $('input:checkbox[name="productList"]').parent().parent().css('color','#fff')
    }
});

$("#shopFirst").attr('checked', true);
$("#shopFirst").parent().css('border','1px solid #F68B24')

$("#nameRequestRadio").attr('checked', true);
$("#nameRequestRadio").parent().css('border','1px solid #F68B24')


$("#goCSV").on( "click", function() {
    $("#step1").removeClass("active");
    $("#csvDiv").addClass("active");
});

var shopsSelectedCsv = new Array();

// $( 'form' ).submit(function ( e ) {
//
//     e.preventDefault();
//
//     var shopsSelectedCsvArray = new Array();
//     for (const [key, value] of Object.entries(shopsSelectedCsv)) {
//         shopsSelectedCsvArray.push(value.shopUuid);
//     }
//     var shopsSelectedCsvArray = shopsSelectedCsvArray.toString();
//
//     var daFile = $('#csvInput').prop('files')[0]
//     // console.log(daFile)
//
//     // var form = $('#theform').serialize();
//     // var formData = new FormData($(form)[1]);
//
//     //
//     var formData;
//     formData = new FormData();
//     formData.append( 'file', daFile,daFile.name );
//     formData.append('s', shopsSelectedCsvArray);
//     // console.log(formData);
//
//     $.ajax({
//         url: 'http://192.168.1.100/skroutzbot/public/user-profile/save',
//         data: formData,
//         // data: {
//         //     'formData':formData,
//         //     's':shopsSelectedCsvArray,
//         // },
//         processData: false,
//         type: 'POST',
//         success: function ( result ) {
//             console.log('Good',result);
//         },
//         error: function(XMLHttpRequest, textStatus, errorThrown) {
//             console.log("Status: " + textStatus);
//             console.log("Error: " + errorThrown);
//         }
//     });
//
//     e.preventDefault();
// });

function uuidExists(uuid) {
    var obj = shopsSelectedCsv.find(o => o.shopUuid === uuid);
    // var obj = shopsSelectedCsv.find(function( o ) {
    //     return o.shopUuid === uuid;
    // });
    if (obj) {
        return 'checked ';
    }
}

function removeShop(shopUuid) {
    $("#ss-"+shopUuid).remove();
    shopsSelectedCsv = shopsSelectedCsv.filter(function( obj ) {
        return obj.shopUuid !== shopUuid;
    });
}



$("#searchCom").keyup(function () {

    var searchComRequest = null;
    var minlength = 3;
    var value = $(this).val();

    if (value.length >= minlength ) {
        if (searchComRequest != null)
            searchComRequest.abort();
        // console.log(value);

        searchComRequest = $.ajax({
            type: "GET",
            url: "http://192.168.1.100/skroutzbot/public/api/shop/suggest",
            headers: {
                Authorization: 'b443b7226ea805f12d25fa1a23d86c15aa434bea719d4364a35000a061a8bf18920a1cd5fd781d3bca47606d65aae05fc78d82be8f4eedfa01cd8ec5'
            },
            data: {
                's' : value
            },
            dataType: "text",
            success: function(result){

                $("#comTable tr").remove();

                var result = JSON.parse(result);
                // console.log(result);
                var shops = result.shops;
                // console.log(shops);

                $.each(shops, function( key, value ) {
                    $("#comTable").append("<tr>" +
                        "<td><input type='checkbox' name='shopCheckbox[]' id='com-"+value.uuid+"' value='" + value.uuid + "' "+ uuidExists(value.uuid)+"></td>" +
                        "<td><label data-img='"+value.Image+"' for='com-"+value.uuid+"'>"+ value.Name +"</label></td>" +
                        // "<td><a href='" + value.Url + "' target='_blank'>URL</a></td>" +
                        "</tr>");
                })

                $('input:checkbox[name="shopCheckbox[]"]').on("change",function(){
                    var shopUuid = $(this).val();
                    var shopName = $(this).parent().parent().find('label').html();
                    var shopImage = $(this).parent().parent().find('label').data('img');
                    if (this.checked) {
                        shopsSelectedCsv.push({
                            "shopUuid": shopUuid,
                            "shopName": shopName,
                            "shopImage": shopImage
                        });
                        $("#shopsCheckedList").append("<div class='shopChecked relative col m3 l3' id='ss-"+shopUuid+"'>" +
                            "<div onclick=\"removeShop('"+shopUuid+"')\" class='removeShopArray absolute'>X</div>" +
                            "<div class='shopCheckedTitle color-white'>"+shopName+"</div>" +
                            "<div class='shopCheckedImage'><img src='"+shopImage+"' alt=''></div>" +
                            // "<td><a href='" + value.Url + "' target='_blank'>URL</a></td>" +
                            "</div>");
                        // $("#shopsChecked").append("<div > </div>")
                        // console.log(shopsSelectedCsv);
                    } else if (!this.checked) {
                        removeShop(shopUuid)
                    }
                });


            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                console.log("Status: " + textStatus);
                console.log("Error: " + errorThrown);
            }
        });
    } else {
        $("#comTable tr").remove();
    }
});
