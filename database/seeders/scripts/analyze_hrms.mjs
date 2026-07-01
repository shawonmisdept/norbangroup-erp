import * as XLSX from 'xlsx';
import { readFileSync, writeFileSync } from 'fs';

const wb = XLSX.read(readFileSync('c:/Users/shawo/OneDrive/Documents/HRMS Contact Information.xlsx'));

function sheetRows(name, headerRowIndex = 0) {
  const rows = XLSX.utils.sheet_to_json(wb.Sheets[name], { defval: '', header: 1 });
  const headers = rows[headerRowIndex].map((h) => String(h).trim());
  return rows.slice(headerRowIndex + 1).map((row) => {
    const obj = {};
    headers.forEach((h, i) => { if (h) obj[h] = row[i] ?? ''; });
    return obj;
  });
}

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

function parseRole(role) {
  const r = String(role || '').trim();
  if (!r.includes('-')) return null;
  const idx = r.indexOf('-');
  const prefix = r.slice(0, idx);
  const designation = r.slice(idx + 1);
  const department = DEPT_FROM_ROLE[prefix];
  if (!department || !designation) return null;
  return { department, designation, role: r };
}

const employees = sheetRows('Sheet1', 2).filter((r) => r['Emp Code']);
const sheet2 = XLSX.utils.sheet_to_json(wb.Sheets['Sheet2'], { defval: '', header: 1 }).slice(1);

const combos = new Map();
const roles = new Set();
const buildings = new Map();
const deptDefaults = new Map();

for (const emp of employees) {
  const dept = String(emp['Department'] || '').trim();
  const desig = String(emp['Designation'] || '').trim();
  const role = String(emp['Role'] || '').trim();
  const building = String(emp['Building'] || '').trim();
  const floor = String(emp['Floor'] || '').trim();
  if (dept && desig) combos.set(`${dept}|||${desig}`, { department: dept, designation: desig, role: role || null });
  if (role) roles.add(role);
  if (dept && building) {
    if (!deptDefaults.has(dept)) deptDefaults.set(dept, { building, floor });
  }
}

for (const row of sheet2) {
  const dept = String(row[1] || '').trim();
  const building = String(row[2] || '').trim();
  const floor = String(row[3] || '').trim();
  const desig = String(row[4] || '').trim();
  const role = String(row[5] || '').trim();

  if (dept && (building || floor) && !deptDefaults.has(dept)) {
    deptDefaults.set(dept, { building: building || null, floor: floor || null });
  }

  if (role) {
    roles.add(role);
    const parsed = parseRole(role);
    if (parsed) combos.set(`${parsed.department}|||${parsed.designation}`, parsed);
  } else if (dept && desig) {
    combos.set(`${dept}|||${desig}`, { department: dept, designation: desig, role: null });
  }
}

const byDept = {};
for (const item of combos.values()) {
  byDept[item.department] ??= [];
  byDept[item.department].push(item);
}
Object.keys(byDept).sort().forEach((d) => {
  console.log(`\n${d} (${byDept[d].length})`);
  byDept[d].sort((a, b) => a.designation.localeCompare(b.designation)).forEach((x) => console.log(' ', x.designation, x.role || ''));
});

console.log('\nRoles total:', roles.size);
console.log('Combos total:', combos.size);
console.log('Employees:', employees.length);
console.log('Dept defaults:', Object.fromEntries(deptDefaults));

writeFileSync('database/seeders/scripts/_generated_summary.json', JSON.stringify({ byDept, roles: [...roles], deptDefaults: Object.fromEntries(deptDefaults), employeeCount: employees.length }, null, 2));
