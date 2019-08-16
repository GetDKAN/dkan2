/*
 * Hooks to add or modify request information.
 */

const hooks = require('hooks');

/*
 * Using Dredd hooks to set the 64-bit encoded username:password for basic
 * authorization.
 *
 * Another attempt at doing this via dredd.yml's `user` option did not work as
 * intended since it applied it indiscriminately for all request paths and
 * verbs.
 */

// Hooks for authenticated requests for dataset and property endpoints
hooks.before('/api/v1/dataset > Create a dataset > 201 > application/json', (transaction) => {
  transaction.request.headers.Authorization = 'Basic dGVzdHVzZXI6Mmpxek9BblhTOW1tY0xhc3k=';
});
hooks.before('/api/v1/dataset/{uuid} > Replace a dataset > 200 > application/json', (transaction) => {
  transaction.request.headers.Authorization = 'Basic dGVzdHVzZXI6Mmpxek9BblhTOW1tY0xhc3k=';
});
hooks.before('/api/v1/dataset/{uuid} > Update a dataset > 200 > application/json', (transaction) => {
  transaction.request.headers.Authorization = 'Basic dGVzdHVzZXI6Mmpxek9BblhTOW1tY0xhc3k=';
});
hooks.before('/api/v1/dataset/{uuid} > Delete a dataset > 200 > application/json', (transaction) => {
  transaction.request.headers.Authorization = 'Basic dGVzdHVzZXI6Mmpxek9BblhTOW1tY0xhc3k=';
});

// Hooks for property endpoints
hooks.before('/api/v1/{property} > Create a property > 201 > application/json', (transaction) => {
  transaction.request.headers.Authorization = 'Basic dGVzdHVzZXI6Mmpxek9BblhTOW1tY0xhc3k=';
});
hooks.before('/api/v1/{property}/{uuid} > Replace a property > 200 > application/json', (transaction) => {
  transaction.request.headers.Authorization = 'Basic dGVzdHVzZXI6Mmpxek9BblhTOW1tY0xhc3k=';
});
hooks.before('/api/v1/{property}/{uuid} > Update a property > 200 > application/json', (transaction) => {
  transaction.request.headers.Authorization = 'Basic dGVzdHVzZXI6Mmpxek9BblhTOW1tY0xhc3k=';
});
hooks.before('/api/v1/{property}/{uuid} > Delete a property > 200 > application/json', (transaction) => {
  transaction.request.headers.Authorization = 'Basic dGVzdHVzZXI6Mmpxek9BblhTOW1tY0xhc3k=';
});
