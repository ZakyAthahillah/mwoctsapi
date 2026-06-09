import { sleep } from 'k6';
import { fail } from 'k6';
import { login } from '../helpers/auth.js';
import { K6_ENDPOINT, K6_ENDPOINT_P95_MS } from '../config/env.js';
import { findReadOnlyEndpoint } from '../helpers/endpoints.js';
import { getApi } from '../helpers/http.js';

const endpoint = findReadOnlyEndpoint(K6_ENDPOINT);

export const options = {
  vus: 5,
  duration: '2m',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: [`p(95)<${K6_ENDPOINT_P95_MS}`],
  },
};

export function setup() {
  if (!endpoint) {
    fail(`Unknown K6_ENDPOINT: ${K6_ENDPOINT}`);
  }

  return {
    token: login(),
    endpoint,
  };
}

export default function (data) {
  getApi(data.endpoint.path, data.token);
  sleep(1);
}
