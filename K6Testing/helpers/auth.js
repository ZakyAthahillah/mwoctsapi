import http from 'k6/http';
import { check, fail } from 'k6';
import { BASE_URL, K6_EMAIL, K6_PASSWORD } from '../config/env.js';

export function login() {
  if (!K6_EMAIL || !K6_PASSWORD) {
    fail('Set K6_EMAIL and K6_PASSWORD before running this script.');
  }

  const response = http.post(
    `${BASE_URL}/api/login`,
    JSON.stringify({
      email: K6_EMAIL,
      password: K6_PASSWORD,
    }),
    {
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      tags: {
        name: 'POST /api/login',
      },
    },
  );

  check(response, {
    'login returns 200': (res) => res.status === 200,
    'login has success true': (res) => res.json('success') === true,
    'login returns token': (res) => Boolean(res.json('data.authorization.token')),
  });

  const token = response.json('data.authorization.token');

  if (!token) {
    fail(`Login failed with status ${response.status}: ${response.body}`);
  }

  return token;
}

