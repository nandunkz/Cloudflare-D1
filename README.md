# Cloudflare D1 Query Builder

Cloudflare D1 Query Builder adalah sebuah library PHP yang dirancang untuk memudahkan pembuatan dan eksekusi query SQL pada database Cloudflare D1. Library ini dibuat oleh [nandunkz](https://github.com/nandunkz) dan dapat ditemukan di [sini](https://github.com/nandunkz/Cloudflare-D1).

## Fitur

- **Select**: Memilih kolom dari tabel.
- **Raw**: Menambahkan ekspresi SQL mentah.
- **Join**: Menggabungkan tabel.
- **Union**: Menggabungkan hasil query.
- **Where**: Menambahkan kondisi pada query.
- **Order By**: Mengurutkan hasil query.
- **Group By**: Mengelompokkan hasil query.
- **Limit**: Membatasi jumlah hasil query.
- **Offset**: Mengatur offset hasil query.
- **Insert**: Menyisipkan data ke dalam tabel.
- **Update**: Memperbarui data dalam tabel.
- **Delete**: Menghapus data dari tabel.
- **Get**: Mengeksekusi query dan mendapatkan hasil.
- **First**: Mendapatkan hasil pertama dari query.

## Instalasi
Pastikan Anda telah mengatur .env berikut:
    - `CLOUDFLARE_ACCOUNT_ID`
    - `CLOUDFLARE_DATABASE_ID`
    - `CLOUDFLARE_API_KEY`

```php
<?php
use App\Helpers\CloudflareD1;
// Memilih tabel
$results = CloudflareD1::table('users')
->select(['id', 'name', 'email'])
->where('status', '=', 'active')
->orderBy('name')
->limit(10)
->get();
print_r($results);
// Menyisipkan data
$insertResult = CloudflareD1::table('users')
->insert([
'name' => 'John Doe',
'email' => 'john.doe@example.com',
'status' => 'active'
]);
print_r($insertResult);
// Memperbarui data
$updateResult = CloudflareD1::table('users')
->where('id', '=', 1)
->update([
'email' => 'new.email@example.com'
]);
print_r($updateResult);
// Menghapus data
$deleteResult = CloudflareD1::table('users')
->where('id', '=', 1)
->delete();
print_r($deleteResult);
```
