# To Do
- [ ] AS Admin, SCREEN Data Dosen & Mahasiswa
  - Import button pending?
  - Test on frontend

&nbsp;

# API Endpoints
Check test.http to test with no frontend
| Method    | Endpoint                                    | Action        |
| --------- | ------------------------------------------- | ------------- |
| GET       | `/api/mahasiswas?search=adrian&per_page=10` | List + search |
| POST      | `/api/mahasiswas`                           | Create        |
| GET       | `/api/mahasiswas/{id}`                      | Show          |
| PUT/PATCH | `/api/mahasiswas/{id}`                      | Update        |
| DELETE    | `/api/mahasiswas/{id}`                      | Delete        |
| GET       | `/api/dosens?search=adrian&per_page=10`     | List + search |
| POST      | `/api/dosens`                               | Create        |
| GET       | `/api/dosens/{id}`                          | Show          |
| PUT/PATCH | `/api/dosens/{id}`                          | Update        |
| DELETE    | `/api/dosens/{id}`                          | Delete        |
| POST      | `/api/mahasiswas/import`| `multipart/form-data` with `file` |
| POST      | `/api/dosens/import`    | `multipart/form-data` with `file` |

&nbsp;

# Terminal

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

### Troubleshooting
```php
php artisan tinker
```