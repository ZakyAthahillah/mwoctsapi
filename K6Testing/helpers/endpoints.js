import { K6_PER_PAGE, K6_PERIOD_END, K6_PERIOD_START } from '../config/env.js';

export function readOnlyEndpointGroups() {
  return [
    { id: 'me', label: 'Me', path: '/api/me' },
    { id: 'profile', label: 'Profile', path: '/api/profile' },
    { id: 'dashboard', label: 'Dashboard', path: '/api/dashboard' },
    { id: 'monitor', label: 'Monitor', path: `/api/monitor?per_page=${K6_PER_PAGE}&period_start=${K6_PERIOD_START}&period_end=${K6_PERIOD_END}` },
    { id: 'downtimes', label: 'Downtimes', path: `/api/downtimes?per_page=${K6_PER_PAGE}&period_start=${K6_PERIOD_START}&period_end=${K6_PERIOD_END}` },
    { id: 'reportings', label: 'Reportings', path: `/api/reportings?per_page=${K6_PER_PAGE}` },
    { id: 'reporting_types', label: 'Reporting types', path: '/api/reportings/types' },
    { id: 'reporting_reports', label: 'Reporting reports', path: `/api/reporting-reports?per_page=${K6_PER_PAGE}` },
    { id: 'reporting_report_statuses', label: 'Reporting report statuses', path: '/api/reporting-reports/statuses' },
    { id: 'jobs', label: 'Jobs', path: `/api/jobs?per_page=${K6_PER_PAGE}` },
    { id: 'jobs_new', label: 'Jobs new', path: `/api/jobs/new?per_page=${K6_PER_PAGE}` },
    { id: 'jobs_on_progress', label: 'Jobs on progress', path: `/api/jobs/on-progress?per_page=${K6_PER_PAGE}` },
    { id: 'jobs_extend', label: 'Jobs extend', path: `/api/jobs/extend?per_page=${K6_PER_PAGE}` },
    { id: 'jobs_waiting_approval', label: 'Jobs waiting approval', path: `/api/jobs/waiting-for-approval?per_page=${K6_PER_PAGE}` },
    { id: 'jobs_finish', label: 'Jobs finish', path: `/api/jobs/finish?per_page=${K6_PER_PAGE}` },
    { id: 'mtbf', label: 'MTBF', path: '/api/mtbf?type=monthly&year=2026' },
    { id: 'mttr', label: 'MTTR', path: '/api/mttr?type=monthly&year=2026' },
    { id: 'fbdts', label: 'FBDTs', path: `/api/fbdts?per_page=${K6_PER_PAGE}` },
    { id: 'targets', label: 'Targets', path: `/api/targets?per_page=${K6_PER_PAGE}&year=2026` },
    { id: 'areas', label: 'Areas', path: `/api/areas?per_page=${K6_PER_PAGE}` },
    { id: 'divisions', label: 'Divisions', path: `/api/divisions?per_page=${K6_PER_PAGE}` },
    { id: 'groups', label: 'Groups', path: `/api/groups?per_page=${K6_PER_PAGE}` },
    { id: 'informants', label: 'Informants', path: `/api/informants?per_page=${K6_PER_PAGE}` },
    { id: 'machines', label: 'Machines', path: `/api/machines?per_page=${K6_PER_PAGE}` },
    { id: 'operations', label: 'Operations', path: `/api/operations?per_page=${K6_PER_PAGE}` },
    { id: 'parts', label: 'Parts', path: `/api/parts?per_page=${K6_PER_PAGE}` },
    { id: 'positions', label: 'Positions', path: `/api/positions?per_page=${K6_PER_PAGE}` },
    { id: 'reasons', label: 'Reasons', path: `/api/reasons?per_page=${K6_PER_PAGE}` },
    { id: 'serial_numbers', label: 'Serial numbers', path: `/api/serial-numbers?per_page=${K6_PER_PAGE}` },
    { id: 'shifts', label: 'Shifts', path: `/api/shifts?per_page=${K6_PER_PAGE}` },
    { id: 'technicians', label: 'Technicians', path: `/api/technicians?per_page=${K6_PER_PAGE}` },
  ];
}

export function readOnlyEndpoints() {
  return readOnlyEndpointGroups().map((endpoint) => endpoint.path);
}

export function findReadOnlyEndpoint(idOrPath) {
  return readOnlyEndpointGroups().find((endpoint) => endpoint.id === idOrPath || endpoint.path === idOrPath);
}
