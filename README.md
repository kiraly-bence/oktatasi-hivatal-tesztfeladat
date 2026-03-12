# Pontszámító Kalkulátor

## Előfeltételek

* PHP 8.1+
* Composer
* npm

## Telepítés

* Composer csomagok telepítése

```sh
composer install
```

* npm csomagok telepítése és buildelése

```sh
npm install
npm run build
```

* Futtatás

```sh
php -S localhost:8080 -t public
```

## Tesztelés

### PHPUnit

```sh
./vendor/bin/phpunit
```

### Példa request

POST `localhost:8080/api/calculate-points`

```json
{
    "valasztott-szak": {
        "egyetem": "ELTE",
        "kar": "IK",
        "szak": "Programtervező informatikus"
    },
    "erettsegi-eredmenyek": [
        {
            "nev": "magyar nyelv és irodalom",
            "tipus": "közép",
            "eredmeny": "70%"
        },
        {
            "nev": "történelem",
            "tipus": "közép",
            "eredmeny": "80%"
        },
        {
            "nev": "matematika",
            "tipus": "emelt",
            "eredmeny": "90%"
        },
        {
            "nev": "angol nyelv",
            "tipus": "közép",
            "eredmeny": "94%"
        },
        {
            "nev": "informatika",
            "tipus": "közép",
            "eredmeny": "95%"
        }
    ],
    "tobbletpontok": [
        {
            "kategoria": "Nyelvvizsga",
            "tipus": "B2",
            "nyelv": "angol"
        },
        {
            "kategoria": "Nyelvvizsga",
            "tipus": "C1",
            "nyelv": "német"
        }
    ]
}
```