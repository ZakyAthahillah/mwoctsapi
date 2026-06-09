import { sleep } from 'k6';
import { login } from '../helpers/auth.js';
import { readOnlyEndpoints } from '../helpers/endpoints.js';
import { getApi } from '../helpers/http.js';

export const options = {
  stages: [
    { duration: '30s', target: 5 },
    { duration: '30s', target: 80 },
    { duration: '1m', target: 80 },
    { duration: '30s', target: 5 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<2500'],
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
