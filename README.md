## Laravel Trmnl Server

This is a PoC of a TRMNL server, written in Laravel. Inspired by https://github.com/usetrmnl/byos_sinatra

![Screenshot](README_byos-screenshot.png)

### Requirements
* PHP >= 8.2
* ext-imagick
* puppeteer [see Browsershot docs](https://spatie.be/docs/browsershot/v4/requirements)

### Installation

#### Clone the repository

```bash
git clone git@github.com:bnussbau/laravel-trmnl-server.git
```

#### Copy environment file

```bash
cp .env.example .env
php artisan key:generate
```

#### Install dependencies

```bash
composer install
npm i
```

#### Run migrations

```bash
php artisan migrate --seed
```

#### Run the server

To make your server accessible in the network, you can run the following command:

```bash
php artisan serve  --host=0.0.0.0 --port 4567
```

### Usage

If your environment is local, you can access the server at `http://localhost:4567` and login with user / password
admin@example.com / admin@example.com

#### Add your TRMNL Device

http://localhost:4567/devices

* Add new device
* You can grab the TRMNL Mac Address and API Key from the TRMNL Dashboard. Or debug the incoming request to `/api/setup` to determine.

#### Flash Firmware to point Device to your server
See this YouTube guide: [https://www.youtube.com/watch?v=3xehPW-PCOM](https://www.youtube.com/watch?v=3xehPW-PCOM)

#### Generate Screen

* Edit resources/views/trmnl.blade.php
* To generate the screen, run

```bash
php artisan trmnl:screen:generate
```



### License
MIT

