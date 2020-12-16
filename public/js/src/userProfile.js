$(document).ready( function () {

    var siteUrl = 'http://192.168.1.100/skroutzbot/public/';

    // console.log(window.location.href)
    // console.log(window.location.hostname )
    // console.log(window.location.pathname )
    // console.log(window.location.protocol )

    if ($('#daUserProfileTable').length) {
        $('#daUserProfileTable').DataTable();
    }

    if ($('#showProductListTable').length) {

        var url = $(location).attr('href'),
            parts = url.split("/"),
            uuid = parts[parts.length-1],
            getUrl = siteUrl + "user-profile/get-products?profile=" +uuid;

        var showProductListTableArray = [];

        $.ajax({
            type: "POST",
            url: getUrl,
            dataType: 'json',
            success: function(result){

                $.each(result, function(key, value){

                    showProductListTableArray.push({
                        "sku":          value.sku,
                        "competitors":  value.competitors,
                        "ean":          value.ean,
                        "matched":      value.matched,
                        "mpn":          value.mpn,
                        "name":         value.name,
                        "photo":        value.photo,
                        "price":        value.price,
                        "update":       value.update,
                    });

                });

                var table = $('#showProductListTable').DataTable( {
                    "paging":   true,
                    "ordering": true,
                    "info":     false,
                    data: showProductListTableArray,
                    'columns': [
                        { 'data': 'sku' },
                        { 'data': 'photo' },
                        { 'data': 'name' },
                        { 'data': 'mpn' },
                        { 'data': 'ean' },
                        { 'data': 'matched' },
                        { 'data': 'competitors' },
                        { 'data': 'price' },
                        { 'data': 'update' },
                    ],
                }); //Table END


            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                console.log("Status: " + textStatus);
                console.log("Error: " + errorThrown);
            }
        });

    }

});

// $(document).click(function(event) {
//     if (!$(event.target).closest(".modal,.startMatch").length) {
//         $('#matchModal').removeClass("active");
//     }
// });
//
// $('#matchSubmit').on( "click", function(e) {
//     $('#matchModal').removeClass("active");
// })

$(".startMatch").on( "click", function(e) {

    e.preventDefault();

    var matchListProductName = $(this).parent().parent().parent().parent().parent().find('.matchListProductName').html();
    // var matchListProductName = $(this).closest('.matchListProductName').html();

    var matchListProductAnswer = window.confirm("Are you sure you want to match this item with \n"+matchListProductName+" ?");

    if (matchListProductAnswer) {

        // var profile = $(this).closest('[data-profile]').data('profile');
        var sku = $(this).closest('[data-sku]').data('sku');
        // var uuid =$(this).data('product');

        // console.log('profile',profile);
        // console.log('sku',sku);
        // console.log('uuid',uuid);

        var form = $('.matchForm').serialize();
        // console.log(form);

        $.ajax({
            type: "POST",
            url: siteUrl + "user-profile/match",
            data: {
                'match' : form
            },
            success: function(result){

                if (result.status === 200){

                    $('[data-sku='+sku+']').remove();
                }
                console.log(result)
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                console.log("Status: " + textStatus);
                console.log("Error: " + errorThrown);
            }
        });

    }


    // $('#profile').val(profile);
    // $('#sku').val(sku);
    // $('#product').empty();
    // $.each($(this).closest('[data-sku]').find('[data-product]'),function () {
    //     $('#product').append('<option value="'+$(this).attr('data-product')+'">'+$(this).attr('data-product-name')+'</option>');
    // });
});

$("#matchSubmit").on( "click", function(e) {

    e.preventDefault();

    var form = $('#matchForm').serialize();
    var sku = $('#matchForm #sku').val();
    console.log(form);

    $.ajax({
        type: "POST",
        url: siteUrl + "user-profile/match",
        data: {
            'match' : form
        },
        success: function(result){

            if (result.status === 200){

                $('[data-sku='+sku+']').remove();
            }
            console.log(result)
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            console.log("Status: " + textStatus);
            console.log("Error: " + errorThrown);
        }
    });

});

$("#downloadXML").on( "click", function(e) {

    e.preventDefault();

    var form = $('#matchForm').serialize();
    var profile = $(this).attr('data-profile');
    console.log(form);

    $.ajax({
        type: "GET",
        url: siteUrl + "user-profile/download",
        data: {
            'profile' : profile
        },
        success: function(result){

            var link = document.createElement("a");
            // If you don't know the name or want to use
            // the webserver default set name = ''
            link.setAttribute('download', result.filename.replace('uploads/generated/',''));
            link.href = siteUrl + result.filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            console.log(result)
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            console.log("Status: " + textStatus);
            console.log("Error: " + errorThrown);
        }
    });

});

$("#startBrain").on( "click", function(e) {

    e.preventDefault();

    startBrain();

});

function startBrain(){
    $.ajax({
        type: "POST",
        url: siteUrl + "brain/run",
        data: {
        },
        success: function(result){

            if (result.status === 200){

                setTimeout(function () {

                    startBrain();
                },1000);
            }else if (result.status === 201){

                setTimeout(function () {

                    startBrain();
                },1000 * 20);
            }

            $('#resp').empty().append(JSON.stringify(result));
            console.log(result)
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            console.log("Status: " + textStatus);
            console.log("Error: " + errorThrown);
        }
    });
}

