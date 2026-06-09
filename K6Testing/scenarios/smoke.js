import { sleep } from 'k6';
import { login } from '../helpers/auth.js';
import { readOnlyEndpoints } from '../helpers/endpoints.js';
import { getApi } from '../helpers/http.js';

export const options = {
  vus: 1,
  iterations: 1,
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<1000'],
  },
};

export default function () {
  const token = login();

  for (const endpoint of readOnlyEndpoints()) {
    getApi(endpoint, token);
    sleep(0.2);
  }
}

