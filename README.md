# WooCommerce Custom REST API Plugin

Custom REST API extension for WordPress + WooCommerce.

This plugin exposes secure REST endpoints for authentication, products,
cart management, orders,coupons, and payment methods.

------------------------------------------------------------------------

## 🚀 Features

-   JWT Authentication
-   User Registration
-   Products (Simple & Variable)
-   Cart Management
-   Order Creation (from current cart)
-   Retrieve User Orders
-   Enabled Payment Methods API
-   Token-based Protected Endpoints

------------------------------------------------------------------------

## 🧩 Requirements

-   WordPress (latest recommended)
-   WooCommerce (10.x tested)
-   JWT Authentication for WP REST API plugin
-   PHP 8.0+

------------------------------------------------------------------------

## 📦 Installation

1.  Copy plugin folder into:

    /wp-content/plugins/

2.  Activate plugin from:

    WordPress Admin → Plugins

3.  Configure JWT secret key inside `wp-config.php`:

``` php
define('JWT_AUTH_SECRET_KEY', 'your-super-secret-key');
```

------------------------------------------------------------------------

# 🔐 Authentication

All protected endpoints require a valid JWT token.

## Login

**POST**

    /wp-json/jwt-auth/v1/token

### Request Body

``` json
{
  "username": "user@example.com",
  "password": "123456"
}
```

### Response

``` json
{
  "token": "your-jwt-token",
  "user_email": "user@example.com",
  "user_display_name": "User Name"
}
```

## Using Token

Include in request headers:

    Authorization: Bearer YOUR_TOKEN

------------------------------------------------------------------------

# 👤 User Registration

**POST**

    /wp-json/custom/v1/register

### Body

``` json
{
  "email": "user@example.com",
  "password": "123456",
  "first_name": "John",
  "last_name": "Doe"
}
```

------------------------------------------------------------------------

# 🛍 Products API

## Get All Products

**GET**

    /wp-json/custom/v1/products

### Returns

-   Product ID
-   Name
-   Description
-   Regular Price
-   Sale Price
-   Stock Status
-   Images
-   Type (simple / variable)
-   Variations (for variable products)

------------------------------------------------------------------------

## Get Single Product

**GET**

    /wp-json/custom/v1/product/{id}

Example:

    /wp-json/custom/v1/product/25

------------------------------------------------------------------------

# 🛒 Cart API

> 🔒 Requires Authentication

## Add to Cart

**POST**

    /wp-json/custom/v1/cart/add

### Simple Product

``` json
{
  "product_id": 25,
  "quantity": 2
}
```

### Variable Product

``` json
{
  "product_id": 30,
  "variation_id": 45,
  "quantity": 1
}
```

------------------------------------------------------------------------

## Update Cart Quantity

**POST**

    /wp-json/custom/v1/cart/update

``` json
{
  "cart_item_key": "abc123",
  "quantity": 3
}
```

------------------------------------------------------------------------

## Get Cart

**GET**

    /wp-json/custom/v1/cart

### Response Includes

-   Cart Items
-   Quantity
-   Subtotal
-   Total

------------------------------------------------------------------------

## Remove Cart Item

**POST**

    /wp-json/custom/v1/cart/update

``` json
{
  "cart_item_key": "abc123",
  "quantity": 0
}
```

------------------------------------------------------------------------

# 📦 Orders API

> 🔒 Requires Authentication

## Create Order (From Current Cart)

**POST**

    /wp-json/custom/v1/create-order

No body required.

------------------------------------------------------------------------

## Get Current User Orders

**GET**

    /wp-json/custom/v1/orders

------------------------------------------------------------------------

## Get Single Order

**GET**

    /wp-json/custom/v1/orders/{id}

Example:

    /wp-json/custom/v1/orders/120

------------------------------------------------------------------------

# 💳 Payment Methods API

## Get Enabled Payment Methods

**GET**

    /wp-json/custom/v1/payment-methods

### Returns

-   ID
-   Title
-   Description
-   Enabled status

------------------------------------------------------------------------

# 🔒 Security

-   Cart and Order endpoints require JWT authentication.
-   Token expiration depends on JWT plugin configuration.
-   Uses authenticated get_current_user_id() context.

------------------------------------------------------------------------

# 🏗 Architecture Overview

    custom-api-plugin/
    │
    ├── includes/
    │   ├── class-auth.php
    │   ├── class-products.php
    │   ├── class-cart.php
    │   ├── class-orders.php
    │   ├── class-payments.php
    │
    ├── custom-api-plugin.php
    └── README.md

------------------------------------------------------------------------

# 📌 Versioning

Current API version:

    /wp-json/custom/v1/

Future versions:

    /wp-json/custom/v2/

------------------------------------------------------------------------

# 🧪 Testing

Recommended tools:

-   Postman

------------------------------------------------------------------------

# 📄 License

Private project -- internal usage only.
