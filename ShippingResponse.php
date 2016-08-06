<?php
/**
 * ShippingResponse Custom Shipping Endpoint Helper
 *
 * @author foxy.io
 * @copyright foxy.io
 * @version 1.0.0
 * @license MIT http://opensource.org/licenses/MIT
 */

Class ShippingResponse {

  function __construct($cart_details = false) {
    if ($cart_details == false) {
      trigger_error("The cart JSON payload is required to be passed to the ShippingResponse object", E_USER_ERROR);
    }

    $this->rates = array();
    $this->error = false;
    $this->cart_details = $cart_details;
  }

  /**
  * Add a custom rate
  * 
  * @param int $service_id
  * @param float $price
  * @param string $method
  * @param string $service_name
  * @param boolean $add_flat_rate
  * @param boolean $add_handling
  **/
  public function add($service_id, $price, $method = '', $service_name = '', $add_flat_rate = true, $add_handling = true) {

    if ($service_id < 10000) {
      $service_id += 10000;
    }
    if ($add_handling) {
      $price += $this->cart_details['_embedded']['fx:shipment']['total_handling_fee'];
    }
    if ($add_flat_rate) {
      $price += $this->cart_details['_embedded']['fx:shipment']['total_flat_rate_shipping'];
    }
    $this->rates[] = array(
        "service_id" => $service_id,
        "price" => $price,
        "method" => $method,
        "service_name" => $service_name
    );
  }

  /**
  * Hide a rate that has been added
  * 
  * @param int|string|array $selector
  **/
  public function hide($selector) {
    $filtered = $this->filterShippingOptions($selector);
    foreach ($filtered as $rate) {
      $this->rates[$rate]['hide'] = true;
    }
  }

  /**
  * Show a rate that has been hidden
  * 
  * @param int|string|array $selector
  **/
  public function show($selector) {
    $filtered = $this->filterShippingOptions($selector);
    foreach ($filtered as $rate) {
      unset($this->rates[$rate]['hide']);
    }
  }

  /**
  * Alias for hide();
  * 
  * @param int|string|array $selector
  **/
  public function remove($selector) {
    $this->hide($selector);
  }

  /**
  * Update an existing rate's price, service name or method
  * 
  * @param int|string|array $selector
  * @param int|string $modifier
  * @param string $method
  * @param string $service_name
  **/
  public function update($selector, $modifier, $method = false, $service_name = false) {
    $filtered = $this->filterShippingOptions($selector);

    foreach ($filtered as $rate) {
      if (gettype($modifier) == "number" || (gettype($modifier) == "string" && $modifier !== "")) {
        $this->rates[$rate]['price'] = $this->modifyPrice($this->rates[$rate]['price'], $modifier);
      }

      if (gettype($method) == "string") {
        $this->rates[$rate]['method'] = $method;
      }
      if (gettype($service_name) == "string") {
        $this->rates[$rate]['service_name'] = $service_name;
      }
    }
  }

  /**
  * Empty any existing rates and error message
  * 
  **/
  public function reset() {
    $this->rates = array();
    $this->error = false;
  }

  /**
  * Add an error message
  * 
  * @param string $message
  **/
  public function error($message = false) {
    $this->error = $message;
  }

  /**
  * Output the existing rates or error message as a JSON encoded string
  * 
  * @return string
  **/
  public function output($output_as_string = true) {
    $response = array();
    if ($this->error !== false) {
        $response["ok"] = false;
        $response["details"] = $this->error;
    } else {
        $rates = array_filter($this->rates, function($rate) {
            return array_key_exists("hide", $rate) == false || $rate['hide'] == false;
        });
        $response["ok"] = true;
        $response["data"] = array(
            "shipping_results" => $rates
        );
    }
    if ($output_as_string) {
      $response = json_encode($response);
    }
    return $response;
  }

  private function filterShippingOptions($selector) {
    if (gettype($selector) == "integer") {
      foreach ($this->rates as $i => $rate) {
        if ($rate['service_id'] == $selector) {
          return array($i);
        }
      }
    } else if (gettype($selector) == "string") {
      $rate_codes = array();
      $rates = array();
      foreach ($this->rates as $i => $rate) {
        $rates[$i] = $rate['method'] . " " . $rate['service_name'];
      }

      if (strtolower($selector) != "all") {
        $regex = '/(fedex|usps|ups)?\s?([\w\s]+)?/i';
        if (preg_match($regex, $selector, $rate_label)) {
          foreach ($rates as $i => $rate) {
            if (!empty($rate_label[1])) {
              if (strpos(strtolower($rate), strtolower($rate_label[1])) === false) {
                unset($rates[$i]);
                continue;
              }
            }
            if (!empty($rate_label[2])) {
              if (strpos(strtolower($rate), strtolower($rate_label[2])) === false) {
                unset($rates[$i]);
              }
            }
          }
        } else {
          return;
        }
      }

      return array_keys($rates);
    } else if (gettype($selector) == "array") {
      $rate_codes = array();
      foreach ($selector as $code) {
        foreach ($this->rates as $i => $rate) {
          if ($code == $rate["service_id"]) {
            $rate_codes[] = $i;
          }
        }
      }

      return $rate_codes;
    }
  }

  private function modifyPrice($price, $modifier) {
    $regex = '/([\+\-\=\*\/])?(\d+(?:\.\d+)?)(\%)?/';
    preg_match($regex, $modifier, $parts);
    $price = floatval($price);
    $modifyBy = floatval($parts[2]);

    if (!empty($parts[3])) {
      $modifyBy = $price * ($modifyBy / 100);
    }
    $operator = (!empty($parts[1])) ? $parts[1] : "=";
    switch ($operator) {
      case "+":
        $price = $price + $modifyBy;
        break;
      case "-":
        $price = $price - $modifyBy;
        break;
      case "*":
        $price = $price * $modifyBy;
        break;
      case "/":
        $price = $price / $modifyBy;
        break;
      default:
        $price = $modifyBy;
        break;
    }

    return ($price < 0) ? 0 : $price;
  }
}