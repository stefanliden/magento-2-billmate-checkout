# Billmate Checkout Payment Gateway for Magento 2
By Billmate AB - [https://billmate.se](https://billmate.se/ "billmate.se")

## Description
This is a payment module for Magento 2 that allows you to pay using Billmate Checkout. Billmate Checkout is a payment solution from Billmate that creates a iframe payment solution on the Magento 2 Checkout page. 

## Important Note
This repo **only supports Billmate Checkout** and ~~**not Custom Pay~~**.

## COMPATIBILITY Magento versions
2.1.X

2.2.X

2.3.X 

## Documentation
Will be added in an upcoming release.

## Supported Languages
### Admin
* English (en_US)
* Swedish (sv_SE)
### Frontend
* English (en_US)
* Swedish (sv_SE)

## Installation
### Via Code Package
1. Download the latest release zip file.
2. In the root directory of your Magento installation, create the following sub-directory path:  
	```
	app/code/Billmate/
	```
3. Upload the zip files contents into the newly created directory.
4. Run these bash commands in the root Magento installation
	```bash
	php bin/magento setup:upgrade
	php bin/magento setup:static-content:deploy
	```
5. Configure the Billmate Credentials under "Stores" --> "Configuration" --> "Sales" --> "Payment Methods" --> "Billmate Checkout" --> "Credentials"
6. Configure the General under "Stores" --> "Configuration" --> "Sales" --> "Payment Methods" --> "Billmate Checkout" --> "General"
7. Make a test purchase for every payment method to verify that you have made the correct settings.
