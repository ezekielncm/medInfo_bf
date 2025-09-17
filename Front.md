Parfait ✅
Je vais condenser **uniquement la partie API contractuelle** du cahier des charges, pour en faire un **document utilisable par une équipe front-end**.

Ce document est **un contrat d’API REST** : il décrit tous les endpoints, les payloads d’entrée, les réponses JSON et les codes de statut. Pas de blabla, que du technique.

---

# 📄 Contrat API — MedInfo Burkina (Laravel, v1)

**Base URL :**

* Dev : `http://localhost:8000/api/v1`
* Prod : `https://medinfo.example.com/api/v1`

**Authentification :**

* Jetstream/Sanctum → Token JWT.
* Tous les endpoints protégés nécessitent :

  ```
  Authorization: Bearer <token>
  ```

---

## 🔑 Auth

### `POST /auth/login`

* Body :

  ```json
  { "email": "user@mail.com", "password": "secret123" }
  ```
* Réponses :

  * `200 OK` :

    ```json
    { "token": "jwt-token", "user": { "id": 1, "name": "John", "roles": ["doctor"] } }
    ```
  * `401 Unauthorized`

### `POST /auth/register`

* Body :

  ```json
  { "name": "John", "email": "john@mail.com", "password": "secret123", "role": "patient" }
  ```
* Réponses :

  * `201 Created` → `{ "user": {...}, "token": "..." }`
  * `400 Bad Request`

### `POST /auth/logout`

* Header : `Authorization: Bearer <token>`
* Réponse : `204 No Content`

### `POST /auth/password/forgot`

* Body :

  ```json
  { "email": "user@mail.com" }
  ```
* Réponse : `200 OK` (mail envoyé si utilisateur existe).

### `POST /auth/password/reset`

* Body :

  ```json
  { "token": "reset-token", "email": "user@mail.com", "password": "newPassword123" }
  ```
* Réponse : `200 OK`

---

## 👥 Patients

### `GET /patients`

* Réponse :

  ```json
  {
    "status": "success",
    "data": [
      { "id": 1, "national_id": "BF1234", "dob": "1990-01-01", "user": {...} }
    ],
    "meta": { "current_page": 1, "per_page": 20, "total": 50 }
  }
  ```

### `GET /patients/{id}`

* Réponse :

  ```json
  { "id": 1, "national_id": "BF1234", "dob": "1990-01-01", "user": {...}, "doctors": [...] }
  ```

### `POST /patients`

* Body :

  ```json
  { "name": "Jane Doe", "email": "jane@mail.com", "password": "secret123", "national_id": "BF9876" }
  ```
* Réponse :

  ```json
  { "id": 2, "user": {...} }
  ```

### `PUT /patients/{id}`

* Body (exemple mise à jour téléphone) :

  ```json
  { "phone": "+22670123456" }
  ```
* Réponse : `200 OK`

### `DELETE /patients/{id}`

* Réponse : `204 No Content`

---

## 🧑‍⚕️ Doctors

### `GET /doctors`

* Réponse :

  ```json
  { "data": [ { "id": 1, "name": "Dr. X", "specialty": "Cardio" } ] }
  ```

### `POST /doctors/{doctor}/assign-patient/{patient}`

* Réponse :

  ```json
  { "status": "success", "message": "Patient assigned" }
  ```

---

## 📋 Consultations

### `POST /consultations`

* Body :

  ```json
  { "patient_id": 1, "reason": "Headache", "diagnosis": "Migraine" }
  ```
* Réponse :

  ```json
  { "id": 10, "patient_id": 1, "doctor_id": 3, "diagnosis": "Migraine" }
  ```

### `GET /consultations/{id}`

* Réponse :

  ```json
  { "id": 10, "patient": {...}, "doctor": {...}, "diagnosis": "Migraine", "prescriptions": [...] }
  ```

---

## 💊 Prescriptions

### `POST /consultations/{id}/prescriptions`

* Body :

  ```json
  { "medicine": "Paracetamol", "dosage": "500mg", "duration": "5 days" }
  ```
* Réponse :

  ```json
  { "id": 101, "consultation_id": 10, "medicine": "Paracetamol", "dosage": "500mg" }
  ```

---

## 📝 Medical Records

### `GET /patients/{id}/records`

* Réponse :

  ```json
  { "data": [ { "id": 1, "type": "allergy", "description": "Penicillin" } ] }
  ```

### `POST /patients/{id}/records`

* Body :

  ```json
  { "type": "note", "description": "Follow-up in 2 weeks" }
  ```
* Réponse :

  ```json
  { "id": 5, "type": "note", "description": "Follow-up in 2 weeks" }
  ```

---

## 🔬 Lab Results

### `POST /patients/{id}/lab-results`

* Body :

  ```json
  { "test_name": "Blood Test", "result": "Normal", "performed_by": "Lab X" }
  ```
* Réponse :

  ```json
  { "id": 77, "test_name": "Blood Test", "result": "Normal" }
  ```

### `GET /patients/{id}/lab-results`

* Réponse :

  ```json
  { "data": [ { "id": 77, "test_name": "Blood Test", "result": "Normal" } ] }
  ```

---

## 📊 Audit Logs (Admin only)

### `GET /logs`

* Query params : `?action=login_success&from=2025-01-01&to=2025-12-31`
* Réponse :

  ```json
  {
    "data": [
      { "id": 1, "user_id": 3, "action": "login_success", "details": {"ip":"1.1.1.1"} }
    ]
  }
  ```

---

## 🔄 Offline Sync

### `POST /sync`

* Body :

  ```json
  {
    "batch": [
      { "type": "consultation", "client_uuid": "abc123", "patient_id": 1, "reason": "Fever", "diagnosis": "Flu" }
    ]
  }
  ```
* Réponse :

  ```json
  {
    "accepted": [ { "client_uuid": "abc123", "server_id": 99 } ],
    "conflicts": []
  }
  ```

---

# ✅ Résumé

* **Indépendance totale du front** → tous les clients (mobile, web, desktop) consomment cette API.
* **Réponses JSON normalisées**.
* **Authentification via Bearer token (Sanctum)**.
* **Endpoints versionnés (`/api/v1/`)** pour compatibilité future.
* **Prêt pour OpenAPI (Swagger)** : ce contrat peut être converti en YAML/JSON Swagger.

---

👉 Veux-tu que je te génère directement le **fichier OpenAPI YAML complet** basé sur ce contrat (pour Swagger UI/Postman/Insomnia), afin que ton équipe front puisse tester immédiatement les endpoints sans attendre le back ?
