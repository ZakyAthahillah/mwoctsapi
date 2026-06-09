import http from 'k6/http';
import { check } from 'k6';
import { Trend } from 'k6/metrics';
import { BASE_URL } from '../config/env.js';
import { readOnlyEndpoints } from './endpoints.js';

function endpointKey(path) {
  return path.split('?')[0];
}

function metricName(path) {
  return `endpoint_${endpointKey(path).replace(/^\/+/, '').replace(/[^A-Za-z0-9_]/g, '_')}_duration`;
}

const endpointDurationMetrics = {};

readOnlyEndpoints().forEach((path) => {
  const key = endpointKey(path);

  if (!endpointDurationMetrics[key]) {
    endpointDurationMetrics[key] = new Trend(metricName(path), true);
  }
});

export function authHeaders(token) {
  return {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`,
  };
}

export function getApi(path, token, expectedStatus = 200) {
  const key = endpointKey(path);
  const response = http.get(`${BASE_URL}${path}`, {
    headers: authHeaders(token),
    tags: {
      name: `GET ${key}`,
    },
  });

  if (endpointDurationMetrics[key]) {
    endpointDurationMetrics[key].add(response.timings.duration);
  }

  check(response, {
    [`GET ${path} returns ${expectedStatus}`]: (res) => res.status === expectedStatus,
    [`GET ${path} has standardized success field`]: (res) => typeof res.json('success') === 'boolean',
  });

  return response;
}
