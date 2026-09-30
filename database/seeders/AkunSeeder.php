<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AkunSeeder extends Seeder
{
    public function run(): void {}
}

//https://bmp.my.id/bk/api/get_user.php?user=fuad123&pass=abc123        //user
//https://bmp.my.id/bk/api/get_whid.php                                 //store
//https://bmp.my.id/bk/api/get_kategori.php?whid=                       //category
//https://bmp.my.id/bk/api/get_price_all.php?whid=                      //price
//https://bmp.my.id/bk/api/get_price2.php?whid=G01&itemid=PPVCCX5030    //price by product




/*

https://bmp.my.id/bk/api/get_whid.php
[
  {
    "whid": "01",
    "whname": "Gudang"
  },
]



https://bmp.my.id/bk/api/get_kategori.php?whid=M04
[
  {
    "itgrpid": "3002",
    "itgrpname": "Atap Plastik"
  },
]




https://bmp.my.id/bk/api/get_price_all.php?whid=M04
[
  {
    "itemid": "PBP11/4",
    "itemdesc": "Paku Beton Putih 1 1/4\" (3cm)",
    "itgrpid": "000010",
    "itgrpname": "Paku",
    "qty": 42,
    "price": 13650,
    "unit1": "KTK"
  },
]




https://bmp.my.id/bk/api/get_price2.php?whid=M04&itemid=UVMARMER-CM3021
{
  "itemid": "UVMARMER-CM3021",
  "itemdesc": "UV Marmer 2900x1220x3mm CM 3021",
  "whid": "M04",
  "unit": "LBR",
  "stock_qty": 0,
  "avgcost": 0,
  "crcid": "IDR",
  "prices": [
    {
      "prclvlid": "5",
      "level": "Grosir",
      "mu": "IDR",
      "harga1": 0,
      "disc1": "",
      "harga1nett": 0,
      "harga2": 0,
      "disc2": "",
      "harga2nett": 0,
      "harga3": 0,
      "disc3": "",
      "harga3nett": 0,
      "pricedate": null,
      "lastupd": null
    },
    {
      "prclvlid": "25",
      "level": "Jateng",
      "mu": "IDR",
      "harga1": 0,
      "disc1": "",
      "harga1nett": 0,
      "harga2": 0,
      "disc2": "",
      "harga2nett": 0,
      "harga3": 0,
      "disc3": "",
      "harga3nett": 0,
      "pricedate": null,
      "lastupd": null
    },
    {
      "prclvlid": "20",
      "level": "Jatim",
      "mu": "IDR",
      "harga1": 0,
      "disc1": "",
      "harga1nett": 0,
      "harga2": 0,
      "disc2": "",
      "harga2nett": 0,
      "harga3": 0,
      "disc3": "",
      "harga3nett": 0,
      "pricedate": null,
      "lastupd": null
    },
    {
      "prclvlid": "15",
      "level": "Lombok",
      "mu": "IDR",
      "harga1": 0,
      "disc1": "",
      "harga1nett": 0,
      "harga2": 0,
      "disc2": "",
      "harga2nett": 0,
      "harga3": 0,
      "disc3": "",
      "harga3nett": 0,
      "pricedate": null,
      "lastupd": null
    },
    {
      "prclvlid": "10",
      "level": "Member",
      "mu": "IDR",
      "harga1": 0,
      "disc1": "",
      "harga1nett": 0,
      "harga2": 0,
      "disc2": "",
      "harga2nett": 0,
      "harga3": 0,
      "disc3": "",
      "harga3nett": 0,
      "pricedate": null,
      "lastupd": null
    },
    {
      "prclvlid": "1",
      "level": "Partai kecil",
      "mu": "IDR",
      "harga1": 0,
      "disc1": "",
      "harga1nett": 0,
      "harga2": 0,
      "disc2": "",
      "harga2nett": 0,
      "harga3": 0,
      "disc3": "",
      "harga3nett": 0,
      "pricedate": null,
      "lastupd": null
    },
    {
      "prclvlid": "0",
      "level": "Retail",
      "mu": "IDR",
      "harga1": 0,
      "disc1": "",
      "harga1nett": 0,
      "harga2": 0,
      "disc2": "",
      "harga2nett": 0,
      "harga3": 0,
      "disc3": "",
      "harga3nett": 0,
      "pricedate": null,
      "lastupd": null
    }
  ]
}


*/