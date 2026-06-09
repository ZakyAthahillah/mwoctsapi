import { sleep } from 'k6';
import { login } from '../helpers/auth.js';
import { readOnlyEndpoints } from '../helpers/endpoints.js';
import { getApi } from '../helpers/http.js';

export const options = {
  vus: 5,
  duration: '2m',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<750'],
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

