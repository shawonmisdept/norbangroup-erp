/**
 * Generate Head Office HRMS seed PHP files from Excel workbook.
 *
 * Usage: node database/seeders/scripts/generate_hrms_seed_data.mjs [path-to-xlsx]
 */
import * as XLSX from 'xlsx';
import { readFileSync, writeFileSync, mkdirSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dir = dirname(fileURLToPath(import.meta.url));
const defaultXlsx = 'c:/Users/shawo/OneDrive/Documents/HRMS Contact Information.xlsx';
const xlsxPath = process.argv[2] || defaultXlsx;
const outDir = join(__dir, '../data');

const DEPT_FROM_ROLE = {
  Admin: 'Admin & HR',
  IT: 'IT',
  Accounts: 'Accounts & Finance',
  Audit: 'Audit',
  Commercial: 'Commercial',
  Procurement: 'Procurement',
  Merchandising: 'Merchandising',
  Design: 'Design',
  CAD: 'CAD',
  MIS: 'MIS',
  Production: 'Production',
};

function sheetRows(wb, name, headerRowIndex = 0) {
  const rows = XLSX.utils.sheet_to_json(wb.Sheets[name], { defval: '', header: 1 });
  const headers = rows[headerRowIndex].map((h) => String(h).trim());
  return rows.slice(headerRowIndex + 1).map((row) => {
    const obj = {};
    headers.forEach((h, i) => {
      if (h) obj[h] = row[i] ?? '';
    });
    return obj;
  });
}

function excelDate(value) {
  if (value === '' || value === null || value === undefined) return null;
  const n = Number(value);
  if (!Number.isFinite(n) || n <= 0) {
    const s = String(value).trim();
    return s === '' ? null : s;
  }
  const date = new Date(Date.UTC(1899, 11, 30 + n));
  return date.toISOString().slice(0, 10);
}

function cleanPhone(value) {
  return String(value || '').replace(/[^\d+]/g, '') || null;
}

function cleanString(value) {
  const s = String(value ?? '').trim();
  return s === '' ? null : s;
}

function empCode(value) {
  return String(value).trim();
}

function parseRole(role) {
  const r = String(role || '').trim();
  if (!r || r === 'No Need' || !r.includes('-')) return null;
  const idx = r.indexOf('-');
  const prefix = r.slice(0, idx);
  const designation = r.slice(idx + 1);
  const department = DEPT_FROM_ROLE[prefix];
  if (!department || !designation) return null;
  return { department, designation, role: r };
}

function floorNumber(name) {
  const s = String(name || '').trim().toLowerCase();
  if (!s) return null;
  if (s.includes('ground')) return 0;
  const m = s.match(/(\d+)/);
  return m ? Number(m[1]) : null;
}

function phpString(value) {
  if (value === null) return 'null';
  return `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
}

function phpExport(value, indent = 0) {
  const pad = '    '.repeat(indent);
  if (value === null) return 'null';
  if (typeof value === 'boolean') return value ? 'true' : 'false';
  if (typeof value === 'number') return String(value);
  if (typeof value === 'string') return phpString(value);
  if (Array.isArray(value)) {
    if (value.length === 0) return '[]';
    const inner = value.map((v) => `${'    '.repeat(indent + 1)}${phpExport(v, indent + 1)},`).join('\n');
    return `[\n${inner}\n${pad}]`;
  }
  const entries = Object.entries(value).map(
    ([k, v]) => `${'    '.repeat(indent + 1)}${phpString(k)} => ${phpExport(v, indent + 1)},`
  );
  return `[\n${entries.join('\n')}\n${pad}]`;
}

const wb = XLSX.read(readFileSync(xlsxPath));
const employees = sheetRows(wb, 'Sheet1', 2).filter((r) => r['Emp Code']);
const sheet2 = XLSX.utils.sheet_to_json(wb.Sheets['Sheet2'], { defval: '', header: 1 }).slice(1);

const combos = new Map();
const roles = new Set();
const deptLocationVotes = new Map();

for (const emp of employees) {
  const department = cleanString(emp['Department']);
  const designation = cleanString(emp['Designation']);
  const role = cleanString(emp['Role']);
  const building = cleanString(emp['Building']);
  const floor = cleanString(emp['Floor']);

  if (department && designation) {
    combos.set(`${department}|||${designation}`, {
      department,
      designation,
      role: role && role !== 'No Need' ? role : null,
    });
  }

  if (role && role !== 'No Need') roles.add(role);

  if (department && building && floor) {
    const key = `${department}|||${building}|||${floor}`;
    deptLocationVotes.set(key, (deptLocationVotes.get(key) || 0) + 1);
  }
}

for (const row of sheet2) {
  const role = cleanString(row[5]);
  if (role && role !== 'No Need') {
    roles.add(role);
    const parsed = parseRole(role);
    if (parsed) combos.set(`${parsed.department}|||${parsed.designation}`, parsed);
  }
}

const deptDefaults = {};
for (const department of new Set([...combos.values()].map((c) => c.department))) {
  const votes = [...deptLocationVotes.entries()]
    .filter(([key]) => key.startsWith(`${department}|||`))
    .sort((a, b) => b[1] - a[1]);
  if (votes.length) {
    const [, building, floor] = votes[0][0].split('|||');
    deptDefaults[department] = { building, floor };
  } else {
    deptDefaults[department] = { building: null, floor: null };
  }
}

const buildingsMap = new Map();
for (const emp of employees) {
  const building = cleanString(emp['Building']);
  const floor = cleanString(emp['Floor']);
  if (!building) continue;
  if (!buildingsMap.has(building)) buildingsMap.set(building, new Set());
  if (floor) buildingsMap.get(building).add(floor);
}

const buildings = [...buildingsMap.entries()].map(([name, floorsSet]) => ({
  name,
  floors: [...floorsSet].sort().map((name) => ({ name, floor_number: floorNumber(name) })),
}));

const departments = [];
for (const department of Object.keys(deptDefaults).sort()) {
  const items = [...combos.values()]
    .filter((c) => c.department === department)
    .sort((a, b) => a.designation.localeCompare(b.designation));

  const seen = new Set();
  const designations = [];
  for (const item of items) {
    if (seen.has(item.designation)) continue;
    seen.add(item.designation);
    designations.push({ name: item.designation, role: item.role });
  }

  departments.push({
    name: department,
    native_name: null,
    default_building: deptDefaults[department].building,
    default_floor: deptDefaults[department].floor,
    designations,
  });
}

const employeeRows = employees.map((emp) => ({
  employee_code: empCode(emp['Emp Code']),
  name: cleanString(emp['Employee Name']),
  department: cleanString(emp['Department']),
  designation: cleanString(emp['Designation']),
  building: cleanString(emp['Building']),
  floor: cleanString(emp['Floor']),
  role: cleanString(emp['Role']),
  father_name: cleanString(emp["Father's Name*"]),
  mother_name: cleanString(emp["Mother's Name*"]),
  nid_number: cleanString(emp['NID Number']),
  present_address: cleanString(emp['Present Address']),
  permanent_address: cleanString(emp['Permanent Address']),
  email: cleanString(emp['Mail Address']),
  phone: cleanPhone(emp['Mobile']),
  joining_date: excelDate(emp['Join Date']),
  status: 'active',
  employment_type: 'Permanent',
  worker_category: 'Staff',
}));

const orgData = {
  factory: 'Head Office',
  buildings,
  departments,
  roles: [...roles].sort(),
};

const employeeData = {
  factory: 'Head Office',
  employees: employeeRows,
};

mkdirSync(outDir, { recursive: true });

const orgFile = `<?php

/**
 * Head Office organization master — generated from HRMS Contact Information.xlsx
 * Generated: ${new Date().toISOString()}
 */

return ${phpExport(orgData)};
`;

const employeeFile = `<?php

/**
 * Head Office employees — generated from HRMS Contact Information.xlsx (Sheet1)
 * Generated: ${new Date().toISOString()}
 */

return ${phpExport(employeeData)};
`;

writeFileSync(join(outDir, 'head_office_org.php'), orgFile);
writeFileSync(join(outDir, 'head_office_employees.php'), employeeFile);

console.log(`Generated ${employeeRows.length} employees, ${departments.length} departments, ${combos.size} designation combos, ${roles.size} roles.`);
