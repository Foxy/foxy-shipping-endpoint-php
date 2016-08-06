# Foxy Custom Shipping Endpoint for PHP

This is a PHP helper class for creating custom shipping rates for the FoxyCart Custom Shipping Endpoint functionality added in version 2.0

## Example Usage

FoxyCart POSTs to your endpoint script a JSON payload containing information about the customers cart and address. As an example of a simple endpoint using the ShippingResponse helper class:

```php
<?php
require('ShippingResponse.php');
$rawPost = file_get_contents('php://input');
$cart_details = json_decode($rawPost, true);
$rates = new ShippingResponse($cart_details);

$rates->add(10001, 5, 'FoxyPost', 'Standard');
$rates->add(10002, 15.50, 'FoxyPost', 'Express');

header('Content-Type: application/json');
print $rates->output();
```
#### Payload

For an example of the payload that is sent to your endpoint, please refer to the [example on the FoxyCart wiki](https://wiki.foxycart.com/v/2.0/shipping#custom_shipping_endpoint).

## Getting Started

To start creating your custom rates, you first need to create the `ShippingResponse` object. The requires that you've included the `ShippingResponse.php` class file in your script, and you pass the JSON object of the cart data payload your endpoint received when creating the object:

```php
require('ShippingResponse.php');
$rawPost = file_get_contents('php://input');
$cart_details = json_decode($rawPost, true);
$rates = new ShippingResponse($cart_details);
```

### Helper Functions

The `ShippingResponse` class adds a number of functions you can utilise to execute your custom shipping logic:

#### `add()`

Adds an additional shipping option to the checkout.

##### Parameters:

* `service_id` _(Number)_ - Minimum of 10000. Can not be the same as another rate
* `price` _(Number)_ - A number to two decimal places
* `method` _(String)_
* `service_name` _(String)_
* `add_flat_rate` _(Boolean)_ - Optional, default: `true` - If true, any applicable flat rates will be added onto the price of this rate
* `add_handling` _(Boolean)_ - Optional, default: `true` - If true, any applicable handling fees will be added onto the price of this rate

##### Example:

```php
$rates->add(10001, 5, 'FoxyPost', 'Standard');  
$rates->add(10002, 0, false, 'Free Shipping', false, false);
```

##### Notes:
 * If the `service_id` provided is less than 10000, the provided rate will be added to it (eg: if 125 is provided as the `service_id`, the resulting id will actually be 10125).
 * Make sure that the `service_id` you use doesn't duplicate a id for an existing rate. 
 * You don't have to provide both the carrier and the service parameters - but at least one of them is required.

#### `hide()`

Hides one or many existing shipping options.

##### Parameters:

* `selector` _(Number, String or Array)_ - Can be the service id of a rate, a string containing the carrier or the service (or a combination of) or an array of service ids.

##### Example:
```php
$rates->hide(1);  // Will hide rate 1
$rates->hide('all');  // Will hide all rates
$rates->hide('FedEx');  // Will hide all rates for FedEx
$rates->hide('Overnight');  // Will hide all rates with a service name that contains 'Overnight'
$rates->hide('USPS Express');  // Will hide any rates from USPS that contain the word 'Express'
$rates->hide([1,2,5,7]);  // Will hide rates with codes 1,2,5 and 7
```

##### Notes: 
 * Any rates that are still hidden at the end of the custom logic block will be removed and not passed back to the checkout in the response.

#### `show()`

Shows one or many existing shipping options that have previously been hidden.

##### Parameters:

* `selector` _(Number, String or Array)_ - Can be the service id of a rate, a string containing the carrier or the service (or a combination of) or an array of service ids.

##### Example:

```php
$rates->show(1);  // Will show rate 1
$rates->show('all');  // Will show all rates
$rates->show('FedEx');  // Will show all rates for FedEx
$rates->show('Overnight');  // Will show all rates with a service name that contains 'Overnight'
$rates->show('USPS Express');  // Will show any rates from USPS that contain the word 'Express'
$rates->show([1,2,5,7]);  // Will show rates with codes 1,2,5 and 7
```

#### `update()`

Updates one or many existing shipping options.

##### Parameters:

* `selector` _(Number, String or Array)_ - Can be the service id of a rate, a string containing the carrier or the service (or a combination of) or an array of service ids.
* `modifier` _(String or Number)_ - Can either be a number (which sets the price to match) or a string containing the operator and a number eg `+20` , `-10` , `*2` , `/2.5` , `=15` . You can also append the string with a `%` sign to make the operation based on a percentage, eg `+20%` - add 20%, `-20%` - less 20%, `/20%` - divide by 20%, `*20%` - multiply by 20%.
* `method` _(String)_ - Optional, if provided replaces the current method
* `service_name` _(String)_ - Optional, if provided replaces the current service name

##### Examples:

```php
$rates->update(1, 5);  // Will set rate 1 to be $5
$rates->update('all', '*2');  // Will set all current rates to double their current cost
$rates->update('FedEx', '+5');  // Will set all rates for FedEx to be $5 more than what they are currently
$rates->update('Overnight', '-5');  // Will set all rates with a service name that contains 'Overnight' to be $5 less than currently set
$rates->update('USPS Express', '=6');  // Will set any rates from USPS that contain the word 'Express' to be $6
$rates->update([1,2,5,7], '/2');  // Will set rates with codes 1,2,5 and 7 to be half their current cost
$rates->update('USPS', '+20%');  // Will add 20% of the current rate to each of the USPS rates
$rates->update('USPS Ground', false, false, 'Super Saver');  // Will change “USPS Ground” to be called “USPS Super Saver”
```

#### `reset()`

Resets the shipping results to be empty and clears out any error message.

##### Example:

```php
$rates->reset();
```

#### `error()`

Set an error response for the rates. If both an error message and valid shipping rates are present, the error message will take precendence.

##### Parameters:

* `message` _(String)_ - If not passed, any existing error message will be cleared

##### Example:

```php
$rates->error("Sorry, we can't ship to Canada");
```

#### `output()`

Returns the current rates or error message as a JSON encoded string

##### Parameters:

* `output_as_string` _(Boolean)_  - Optional, default: `true` - If true, the output will be passed through `json_encode()` to return the JSON object as a string.

##### Example:

```php
print $rates->output();
```

## Code Examples

The following are examples of the custom logic for common shipping requirements. They all assume a basic endpoint with the following code, and that the `ShippingReponse.php` class file exists in the same directory:

```php
<?php
require('ShippingResponse.php');
$rawPost = file_get_contents('php://input');
$cart_details = json_decode($rawPost, true);
$rates = new ShippingResponse($cart_details);

// Custom logic placed here

header('Content-Type: application/json');
print $rates->output();
```


#### Example 1 - Conditional free shipping

* Free shipping if the customer orders $40 or more, otherwise it's $5 flat rate

```php
$rates->add(10001, 5, 'FoxyPost', 'Standard');
if ($cart_details['_embedded']['fx:shipment']['total_item_price'] >= 40) {
  $rates->update(10001, 0);
}
```

#### Example 2 - Adjust rates based on weight and item count

* 3 default shipping options: standard, priority and express.
* If the total weight of the cart is greater that 10, adjust the shipping costs.
* If there are more than 5 products, remove the express option.

```php
$rates->add(10001, 5, 'FoxyPost', 'Standard');
$rates->add(10002, 9.45, 'FoxyPost', 'Priority');
$rates->add(10003, 10, 'FoxyPost', 'Express (Next Day)');
 
if ($cart_details['_embedded']['fx:shipment']['total_weight'] > 10) {
  $rates->update(10001, 6);
  $rates->update(10002, 10);
  $rates->update(10003, 11.99);
}
 
if (count($cart_details['_embedded']['fx:items']) > 5) {
  $rates->remove(10003);
}
```

#### Example 3 - Postage calculated based on the number of products

* Postage is calculated as a base price per product, with each subsequent product adding an additional cost.
* Two different groups of shipping options are presented, one for local delivery within the US, and one for international addresses based off of the shipping country.

```php
$item_count = count($cart_details['_embedded']['fx:items']);
if ($cart_details['_embedded']['fx:shipment']['country'] == "US") {
  $postage = 10 + (($item_count - 1) * 0.50);
  $rates->add(10001, postage, 'FoxyPost', 'Standard');
 
  $postage = 12 + (($item_count - 1) * 1.50);
  $rates->add(10002, postage, 'FoxyPost', 'Express');
} else {
  $postage = 15 + (($item_count - 1) * 2);
  $rates->add(10003, postage, 'FoxyPost', 'International');
}
```

#### Example 4 - Shipping rates based on categories

* Postage is assigned per category.
* If there is a product from CategoryA in the cart, then present express option
* If there is only a product from CategoryB in the cart, provide free shipping as an option
* Ensure that any existing flat rate and handling fees don't get added on to the free shipping option

```php
$hasCategoryA = false;
$hasCategoryB = false;
foreach($cart_details['_embedded']['fx:items'] as $item) {
  switch ($item['_embedded']['fx:item_category']['code']) {
    case "CategoryA":
      $hasCategoryA = true;
      break;
    case "CategoryB":
      $hasCategoryB = true;
      break;
  }
}
if ($hasCategoryB && !$hasCategoryA) {
  $rates->add(10001, 0, '', 'Free Ground Shipping', false, false);
} else if ($hasCategoryA) {
  $rates->add(10002, 5.99, 'FoxyPost', 'Express')
}
```

#### Example 5 - Free shipping based on a coupon

* Postage is flat rate, but free if “free shipping” coupon is present
* Allow free shipping only if a certain coupon code is present. In this example, one with a code of `freeshipping`.

```php
$rates->add(10001, 5, "FoxyPost", "Standard");

foreach ($cart_details['_embedded']['fx:discounts'] as $discount) {
  if ($discount['code'] == "freeshipping") {
    $rates->update(10001, 0);
  }
}
```

#### Example 6 - Pricing based on countries

* Pricing tiers, one for the UK, one for Europe and then the rest of the world

```php
$tier1 = ['GB'];
$tier2 = ['AL', 'AD', 'AM', 'AT', 'BY', 'BE', 'BA', 'BG', 'CH', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FO', 'FI', 'FR', 'GB', 'GE', 'GI', 'GR', 'HU', 'HR', 'IE', 'IS', 'IT', 'LT', 'LU', 'LV', 'MC', 'MK', 'MT', 'NO', 'NL', 'PL', 'PT', 'RO', 'RU', 'SE', 'SI', 'SK', 'SM', 'TR', 'UA', 'VA'];
$country = $cart_details['_embedded']['fx:shipment']['country'];

if (in_array($country, $tier1)) {
  // United Kingdom
  $rates->add(1, 10, 'FoxyPost', 'Standard');
} else if (in_array($country, $tier2)) {
  // Europe
  $rates->add(2, 20, 'FoxyPost', 'International');
} else {
  // Rest of world
  $rates->add(3, 30, 'FoxyPost', 'International');
}
```


## Troubleshooting

#### "Error: This store has not been setup correctly to calculate shipping to this location with this weight."

If you receive this error in your store, that means that either no rates were returned from your custom endpoint, or it didn't return a valid JSON object.

 1. Firstly ensure that your endpoint is set to return at least one rate or an error for all requests.
 1. If you are - ensure that only the JSON output is printed on the page. You can't have any other text on the page.

If you're using PHP 5.6, there is a known issue with that related to the `always_populate_raw_post_data` setting in PHP. It defaults to `0` in that version, and when receiving a JSON POST to the page, will output an error warning on the page like this:

```
PHP Deprecated:  Automatically populating $HTTP_RAW_POST_DATA is deprecated and will be removed in a future version. To avoid this warning set 'always_populate_raw_post_data' to '-1' in php.ini and use the php://input stream instead. in Unknown on line 0
```

To work around this, you could use a more recent version of PHP or you can set the `always_populate_raw_post_data` to `-1` if you have access to your PHP settings. Alternatively you can also set `display_errors` to `Off` to prevent it outputting the warning. On your production environment, `display_errors` should be off anyway for security - but this may affect your development environments too.

## License

Copyright (c) Foxy.io

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.