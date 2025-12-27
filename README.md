# Ξερή / Τάβλι Web API Project

## Περιγραφή Project

Αυτό το project υλοποιεί το παιχνίδι **Ξερή**  ως Web API, κατάλληλο για **1-παίκτη παρουσίαση** μέσω CLI/curl.

Το σύστημα περιλαμβάνει:

* **Authentication με token** (χωρίς password) για κάθε παίκτη.
* Αποθήκευση πλήρους κατάστασης παιχνιδιού σε **MySQL database** (`games` table).
* Λογική παιχνιδιού: deck, χέρια παικτών, τραπέζι, λήψη φύλλων, σειρά παικτών.
* Αναγνώριση **deadlock / game over**.  

Το API είναι πλήρως λειτουργικό για **CLI/curl**, χωρίς GUI, και μπορεί να επεκταθεί για **πλήρες Human-Human παιχνίδι**.

---

## Database Schema

```sql
CREATE DATABASE xeri_game;

USE xeri_game;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    token VARCHAR(32) NOT NULL UNIQUE
);

CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state TEXT NOT NULL,
    current_player INT NOT NULL,
    status VARCHAR(20) NOT NULL
);
```

* `users` → αποθηκεύει τους παίκτες και τα authentication tokens.
* `games` → αποθηκεύει την πλήρη κατάσταση παιχνιδιού σε JSON (`state`) και τρέχοντα παίκτη.

---

## API Endpoints

### 1. `auth.php` — Δημιουργία χρήστη / token

**POST** `/auth.php`

**Παράμετροι (JSON body)**

```json
{
  "name": "Stefanos"
}
```

**Απάντηση**

```json
{
  "name": "Stefanos",
  "token": "3f9d2e4a1b7c9f6a8b5c2d1e4f6a7b8c"
}
```

---

### 2. `start.php` — Δημιουργία νέου παιχνιδιού

**POST** `/start.php`

**Παράμετροι (JSON body)**

```json
{
  "token": "PLAYER_TOKEN"
}
```

**Απάντηση**

```json
{
  "game_id": 1,
  "status": "game created",
  "state": { ... πλήρης κατάσταση παιχνιδιού ... },
  "player_no": 1
}
```

* Αρχικοποιείται deck, χέρια παικτών, τραπέζι.
* Ο πρώτος παίκτης παίρνει θέση `player_no = 1`.

---

### 3. `join.php` — Εγγραφή δεύτερου παίκτη

**POST** `/join.php`

**Παράμετροι (JSON body)**

```json
{
  "game_id": 1,
  "token": "SECOND_PLAYER_TOKEN"
}
```

**Απάντηση**

```json
{
  "status": "player joined",
  "player_no": 2,
  "state": { ... ενημερωμένη κατάσταση παιχνιδιού ... }
}
```

* Προστίθεται ο δεύτερος παίκτης στο παιχνίδι.
* Αν τα χέρια του 2ου παίκτη είναι άδεια, τραβάνουν 4 φύλλα από το deck.
* Το status του παιχνιδιού γίνεται `playing`.

---

### 4. `play.php` — Κίνηση παίκτη

**POST** `/play.php`

**Παράμετροι (JSON body)**

```json
{
  "game_id": 1,
  "token": "PLAYER_TOKEN",
  "card_id": "S5"
}
```

**Λειτουργία**

* Ελέγχει αν ο παίκτης είναι μέρος του παιχνιδιού.
* Ελέγχει αν είναι η σειρά του παίκτη.
* Ελέγχει αν η κάρτα υπάρχει στο χέρι του. 
* Ελέγχει **game over / deadlock**.
* Υπολογίζει νικητή σύμφωνα με τους κανόνες Ξερή.

**Απάντηση**

```json
{
  "ok": true,
  "state": { ... ενημερωμένη κατάσταση ... },
  "next_player": 2
}
```

* Το `state` περιλαμβάνει:

  * `deck` → τα φύλλα στο deck
  * `hands` → τα φύλλα κάθε παίκτη
  * `table` → τα φύλλα στο τραπέζι 
  * `turn` → ποιανού η σειρά
  * `game_over` → true/false 

---

### 5. `state.php` — Λήψη κατάστασης παιχνιδιού

**GET** `/state.php?game_id=1`

**Απάντηση**

```json
{
  "id": 1,
  "state": { ... πλήρης κατάσταση παιχνιδιού ... },
  "current_player": 1,
  "status": "playing"
}
```

* Επιστρέφει **πλήρη κατάσταση παιχνιδιού**.
* Περιλαμβάνει `game_over` όταν το παιχνίδι τελειώσει.

---

## Παραδείγματα CURL

**Δημιουργία χρήστη:**

```bash
curl -X POST -H "Content-Type: application/json" \
-d '{"name":"Stefanos"}' \
https://users.it.teithe.gr/~iee2020163/adise25/auth.php
```

**Έναρξη παιχνιδιού:**

```bash
curl -X POST -H "Content-Type: application/json" \
-d '{"token":"PLAYER_TOKEN"}' \
https://users.it.teithe.gr/~iee2020163/adise25/start.php
```

**Join δεύτερος παίκτης:**

```bash
curl -X POST -H "Content-Type: application/json" \
-d '{"game_id":1,"token":"SECOND_PLAYER_TOKEN"}' \
https://users.it.teithe.gr/~iee2020163/adise25/join.php
```

**Παίκτης παίζει κίνηση:**

```bash
curl -X POST -H "Content-Type: application/json" \
-d '{"game_id":1,"token":"PLAYER_TOKEN","card_id":"S5"}' \
https://users.it.teithe.gr/~iee2020163/adise25/play.php
```

**Λήψη κατάστασης παιχνιδιού:**

```bash
curl https://users.it.teithe.gr/~iee2020163/adise25/state.php?game_id=1
```

---

## Σημειώσεις

* Το API είναι πλήρως λειτουργικό με **CLI/curl**, κατάλληλο για παρουσίαση ή ανάπτυξη frontend GUI.
* Όλα τα δεδομένα παιχνιδιού αποθηκεύονται στη βάση (`state` JSON).
* Το authentication με token επιτρέπει απλή αναγνώριση παίκτη χωρίς password.
* Deadlock / Game over εντοπίζονται αυτόματα.
