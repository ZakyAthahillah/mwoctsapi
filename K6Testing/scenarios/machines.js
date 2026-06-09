import { sleep } from 'k6';
import { login } from '../helpers/auth.js';
import { K6_PER_PAGE } from '../config/env.js';
import { getApi } from '../helpers/http.js';

export const options = {
  stages: [
    { duration: '1m', target: 10 },
    { duration: '3m', target: 20 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.02'],
    http_req_duration: ['p(95)<1000'],
    'http_req_duration{name:GET /api/machines}': ['p(95)<1000'],
  },
};

export function setup() {
  return {
    token: login(),
  };
}

export default function (data) {
  getApi(`/api/machines?per_page=${K6_PER_PAGE}`, data.token);
  sleep(1);
}
