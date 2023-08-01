function delay(callback, ms) {
  var timer = 0;
  return function() {
    var context = this, args = arguments;
    clearTimeout(timer);
    timer = setTimeout(function () {
      callback.apply(context, args);
    }, ms || 0);
  };
}

function populateMerchantName(data) {
  if (typeof data.merchant_id !== "undefined" && typeof data.merchant_name !== "undefined") {
    $('#merchant-id').val(data.merchant_id)
    $('#merchant-name').val(data.merchant_name)
  }
}

$(document).on("keypress","#merchant-code", delay(function (e) {
  e.preventDefault();

  if(e.which == 13) {
    $('#btn-search').trigger('click');
  }

  $('#merchant-code').focus();
}));

$(document).on("keyup",".item-price", delay(function (e) {
  console.log('!!');
}, 500));


$(document).ready(function () {
  var itemIndex = 1;

  setTimeout(() => {
    $('.generate-invoice').trigger('click');
  }, 300);
  
  $('#addMore').click(function () {
    var allItemsValid = true;

    $('.item').each(function () {
      var itemName = $(this).find('.item-name').val();
      var itemQuantity = $(this).find('.item-quantity').val();

      if (itemName === '' || itemQuantity === '') {
        allItemsValid =false;
      }
    });

    if ( ! allItemsValid) {
        $.oc.flashMsg({text: 'Please select an item and quantity before adding more.', 'class': 'error', 'interval' : 3})
        return;
    }

    var newItem = `
    <div class="mb-3 item">
      <label for="item${itemIndex}" class="form-label">Item ${itemIndex + 1}</label>
      <select class="form-select item-name mb-2" name="items[${itemIndex}][item_name]" required>
        <option value="" selected>Select an item</option>
        <option value="Tshirt">Tshirt</option>
        <option value="Hoodie">Hoodie</option>
        <option value="Shoes">Shoes</option>
        <option value="Jeans">Jeans</option>
      </select>
      <select class="form-select item-quantity mb-2" name="items[${itemIndex}][quantity]" required>
        <option value="1" selected>1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>
      </select>
      <input type="number" class="form-control item-price mb-2" name="items[${itemIndex}][price]">
      <button type="button" class="btn btn-danger removeItem">Remove</button>
      <hr>
    </div>
    `;

    $('#itemsContainer').append(newItem);
    itemIndex++;
  });

  $(document).on('change', '.item-name', function () {
      $('.item-price').focus();
      $('.item-price').val('');
  });

  $(document).on('click', '.generate-invoice', function (e) {
    var invoice = generateInvoiceCode();

    $('#invoiceCode').val(invoice);
  });

  $(document).on('click', '.removeItem', function () {
    $(this).closest('.item').remove();
    calculateTotalCharged();
  });

  function calculateTotalCharged() {
    var totalCharged = 0;
    
    $('.item').each(function () {
      var price = parseInt($(this).find('.item-price').val());
      var quantity = parseInt($(this).find('.item-quantity').val());
      totalCharged += price * quantity;
    });
    
    $('#totalCharged').val(totalCharged);

    totalChargedString = addCommas(totalCharged);
    
    if (isNaN(totalChargedString)) {
      totalChargedString = 0;
    }

    $('#total-charged').html('<strong>' + totalChargedString  + '</strong>');
  }
});

function addCommas(nStr)
{
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}


function generateInvoiceCode() {
  var prefix = 'INV-';
  var randomCode = Math.random().toString(30).substr(2, 15).toUpperCase();
  var invoiceCode = prefix + randomCode + '-' + Math.random().toString(30).substr(2, 4).toUpperCase();
  return invoiceCode;
}
