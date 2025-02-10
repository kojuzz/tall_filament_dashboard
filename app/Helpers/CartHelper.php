<?php

use Illuminate\Support\Facades\Cookie;

class CartHelper
{
    // get all cart items from cookie
    public static function getCartItemsFromCookie()
    {
        $cart_items = json_decode(Cookie::get('cart_items'), true);
        if (!$cart_items) {
            $cart_items = [];
        }
        return $cart_items;
    }

    // add items to cart
    public static function addItemsToCart($product_id)
    {
        $cart_items = self::getCartItemsFromCookie();
        $existing_item = null;
        foreach ($cart_items as $key => $item) {
            
        }
    }

    // remove items from the cart

    // add cart items to cookie
    // clear cart items from cookie

    // increment item quantity
    // decrement item quantity

    // calculate grand total
}
