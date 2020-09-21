TrashIt
=====

A PHP application made for customers to register for a trash valet service. Originally geared towards students as a subscription, we will come to the customers house, and grab their garbage from their front door and bring it to the dumpster. All data is saved in a MySQL database.

![Image of Front Page](https://i.ibb.co/mT4rsrF/Screen-Shot-2020-09-21-at-4-11-39-PM.png)

Once registered, users can specify their preferences for days of pickup, and preferred time for pickup.

![Image of Options Page](https://i.ibb.co/pLK5QSc/Screen-Shot-2020-09-21-at-4-15-52-PM.png)

Scripts are ran on the backend to update users next pickup date. Lists are automatically generated for drivers on a daily basis.

![Image of Dashboard](https://i.ibb.co/RN59DSW/Screen-Shot-2020-09-21-at-4-12-23-PM.png)

Braintree is used for payment processing, and monthly charges are handled as such. Webhooks have been implemented to handle canceled credit cards, and past due accounts. When a successful payment is made, it is recorded and shows up on the users transactions page.

Google Maps API is used to ensure the address entered is within a 2 mile radius of FSU.

A PHP Router has been added using .htaccess to make for simpler page routing.