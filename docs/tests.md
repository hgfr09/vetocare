# Test Strategy

## 1. User

### Creation

- [x] USER-001 — Create a valid user

### Update

- [x] USER-004 — Keep current password when plainPassword is omitted

### Validation

- [x] USER-006 — Reject invalid user data
- [x] USER-007 — Reject duplicate email

### Business Rules (BR)

- [x] USER-008 — Assign ROLE_VETO by default

### Security

- [x] USER-003 — Anonymous user cannot access protected API
- [x] USER-009 — Do not expose plainPassword in API response
- [x] USER-010 — Hash password in database
- [x] USER-012 — User can access own profile
- [x] USER-013 — User cannot access another user's profile
- [x] USER-014 — User can update own profile
- [x] USER-011 — User cannot update another user's profile
- [x] USER-005 — Only admin can access all users
- [x] USER-002 — Only admin can delete users
- [x] USER-015 — Admin can access another user's profile

## 2. Animal

### Creation
- [x] ANIMAL-001 — Create a valid animal
  - Test: `testCreateAnimalSuccess`

### Read

_No tests yet_

### Update

_No tests yet_

### Validation

- [x] ANIMAL-002 — Reject owner name that is too short
  - Test: `testCannotCreateAnimalWithInvalidOwnerName`

### Business Rules (BR)
- [x] ANIMAL-003 — Reject a birth date in the future
  - Test: `testCannotCreateAnimalWithDateInFuture`

### Security

_No tests yet_

## 3. Consultation

### Creation

- [x] CONSULT-001 — Create a consultation 

### Read

- [x] CONSULT-004 — Get all consultations of a veterinarian

### Update

_No tests yet._

### Delete

_No tests yet._

### Validation

- [x] CONSULT-002 — Reject consultation without veterinarian

### Business Rules (BR)

- [x] CONSULT-003 — Reject consultation dated before animal's birth date

### Security

_No tests yet._

## 4. Authentication

## 5. Authorization / RBAC

## 6. Business rules

## 7. API / HTTP

## 8. Security

## 9. CI/CD