## Mermaid
```mermaid
classDiagram
    direction TB

    class User {
        -Int UserID
        -String Password
        -String Email
        -String Nama
        +login()
        +logout()
    }

    class Admin {
        -String NomorAdmin
        +kelolaMahasiswa()
        +kelolaDosen()
        +kelolaKelas()
        +kelolaTugas()
    }

    class Dosen {
        -String KodeDosen
        +viewKelas()
        +viewMahasiswa()
        +nilaiTugas()
        +addMahasiswa()
    }

    class Mahasiswa {
        -String NIM
        +viewKelas()
        +viewMahasiswa()
        +viewTugas()
        +uploadTugas()
        +viewNilaiTugas()
    }

    class TrSiswa {
        -string NIM
        -string KelasID
    }

    class Kelas {
        -String KodeKelas
        -String KodeRuangan
        -Enum TipeKelas
        -String PeriodeKelas
        -String DosenID
        -String jam
        -String Hari
        -Int KelasID
        -Int KasusID
        +getListMahasiswa()
        +getListTugas()
        +getDosen()
    }

    class JwbKasus {
        -Int KasusID
        -String SubmisID
        -Date TanggalUpload
        -Int Nilai
        -String NIM
        -Blob File
    }

    class Kasus {
        -Int ClientID
        -Int KasusID
        -String NamaTugas
        -String NamaFile
        -Blob File
    }

    class DataClient {
        -Int ClientID
        -String NPWP
        -String NamaClient
        -String NamaKantor
        -String JenisClient
        -String AlamatClient
        -String AlamatKantor
        -String HPClient
        -String HPKantor
        -String EmailClient
        -String EmailKantor
        -String URLClient
        -String URLKantor
        -String LogoKantor
        -String LogoPerusahaan
    }

    %% Inheritance
    User <|-- Admin
    User <|-- Dosen
    User <|-- Mahasiswa

    %% Admin management / dependency relationships
    Admin ..> Dosen : Mengelola
    Admin ..> Mahasiswa : Mengelola
    Admin ..> Kelas : Mengelola

    %% Dosen - Mahasiswa - Kelas
    Mahasiswa "1" -- "0..*" TrSiswa
    TrSiswa "0..*" -- "1" Kelas

    %% Mahasiswa submits assignments
    Mahasiswa "1" --> "0..*" JwbKasus : uploadTugas

    %% Case / assignment relationships
    Kasus "1" --> "0..*" JwbKasus
    Kasus "1" -- "1" Kelas

    %% Client relationship
    DataClient "1" --> "1" Kasus
```

&nbsp;

## API Endpoints
Check api_test folder

## To Do
- Cek apakah ada email yang sama di DB, supaya tidak bisa insert duplicate email
- Delete tidak bisa karena DB schema/relasi antara kasus dan kelas

## Terminal

### Populate database with dummy data
```php
php artisan migrate:fresh --seed
```

### Initialize empty database
```php
php artisan migrate:fresh
```
### initialize test database 
dengan dummy data yang pasti supaya lbh mudah test API
```php
php artisan db:seed --class=TestSeeder
```

### Launch server to test API
```php
php artisan serve
```

### DB
```php
php artisan tinker
```

List semua objek, di contoh ini merupakan User
```
User::all();
```

List semua tabel
```
Schema::getTableListing();
```
&nbsp;
Lihat nama semua kolom di suatu tabel
```
Schema::getColumnListing('users');
```

Lihat metadata/definisi nilai kolom
```
Schema::getColumns('users');
```

Lihat constraints/behavior/relasi suatu tabel dengan tabel lain, bila ada
```
Schema::getForeignKeys('kelas');
```