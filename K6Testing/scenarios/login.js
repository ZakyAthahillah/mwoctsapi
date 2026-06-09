import { sleep } from 'k6';
import { login } from '../helpers/auth.js';
import { K6_LOGIN_P95_MS, K6_LOGIN_SLEEP_SECONDS } from '../config/env.js';

export const options = {
  stages: [
    { duration: '30s', target: 1 },
    { duration: '1m', target: 5 },
    { duration: '1m', target: 10 },
    { duration: '2m', target: 20 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.02'],
    http_req_duration: [`p(95)<${K6_LOGIN_P95_MS}`],
    'http_req_duration{name:POST /api/login}': [`p(95)<${K6_LOGIN_P95_MS}`],
  },
};

export default function () {
  login();
  sleep(Number(K6_LOGIN_SLEEP_SECONDS));
}
