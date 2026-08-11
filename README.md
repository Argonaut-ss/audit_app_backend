## API Endpoints
Check test.http to test with no frontend
Arti ID adalah internal DB ID, bukan NIM atau Kode Dosen
### Mahasiswa
| Method    | Endpoint                                           | Action                            |
| --------- | -------------------------------------------------- | --------------------------------- |
| GET       | `/api/mahasiswas?search=adrian&per_page=5&?page=1` | List + search                     |
| POST      | `/api/mahasiswas`                                  | Create                            |
| GET       | `/api/mahasiswas/{id}`                             | Show                              |
| PUT/PATCH | `/api/mahasiswas/{id}`                             | Update                            |
| DELETE    | `/api/mahasiswas/{id}`                             | Delete                            |
| POST      | `/api/mahasiswas/import`                           | `multipart/form-data` with `file` |

### Dosen
| Method    | Endpoint                                       | Action                            |
| --------- | ---------------------------------------------- | --------------------------------- |
| GET       | `/api/dosens?search=adrian&per_page=5&?page=1` | List + search                     |
| POST      | `/api/dosens`                                  | Create                            |
| GET       | `/api/dosens/{id}`                             | Show                              |
| PUT/PATCH | `/api/dosens/{id}`                             | Update                            |
| DELETE    | `/api/dosens/{id}`                             | Delete                            |
| POST      | `/api/dosens/import`                           | `multipart/form-data` with `file` |

### Kelas
| Method    | Endpoint                                    | Action        |
| --------- | ------------------------------------------- | ------------- |
| GET       | `/api/kelas?search=LA01&per_page=5&?page=1` | List + search |
| POST      | `/api/kelas`                                | Create        |
| GET       | `/api/kelas/{id}`                           | Show          |
| PUT/PATCH | `/api/kelas/{id}`                           | Update        |
| DELETE    | `/api/kelas/{id}`                           | Delete        |

&nbsp;

## Terminal

### Populate database with dummy data
```php
php artisan migrate:fresh --seed
```

### Initialize empty database
```php
php artisan migrate:fresh
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