import { sleep } from 'k6';
import { login } from '../helpers/auth.js';
import { readOnlyEndpoints } from '../helpers/endpoints.js';
import { getApi } from '../helpers/http.js';

export const options = {
  stages: [
    { duration: '1m', target: 20 },
    { duration: '2m', target: 50 },
    { duration: '2m', target: 100 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<2000'],
  },
};

export function setup() {
  return {
    token: login(),
  };
}

export default function (data) {
  const endpoints = readOnlyEndpoints();
  const endpoint = endpoints[Math.floor(Math.random() * endpoints.length)];

  getApi(endpoint, data.token);
  sleep(1);
}

