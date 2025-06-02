# BOG Payment Gateway for WordPress

**Fast, simple, and free WordPress plugin for BOG payment system integration.**

---

## Features
- One-click payment button for BOG (Bank of Georgia)
- Customizable button and error styles from admin panel
- Callback support for payment status
- Easy integration into any template
- Secure OAuth2 authentication

---

## Installation
1. Upload the plugin folder to `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings → BOG Payments** and enter your Client ID and Client Secret.
4. Customize button and error styles if needed.
5. Use the `bog_payment_link_button()` function in your templates:

```php
echo bog_payment_link_button([
    'amount' => 100,
    'currency' => 'USD',
    'description' => 'Test payment',
    'name' => 'John Doe',
    'order_id' => 'order_123',
]);
```

---

## Author
- **Dmitrii Ivanov**  
- GitHub: [dmitriiivanovcom](https://github.com/dmitriiivanovcom)  
- Telegram: [@Lane_42](https://t.me/Lane_42)

---

## License
GPLv2 or later

---

## Description
Fast, simple, and free WordPress plugin for BOG payment system integration.

---

## Support
For questions and support, contact via Telegram [@Lane_42](https://t.me/Lane_42) or open an issue on GitHub. 