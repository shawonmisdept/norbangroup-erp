import * as XLSX from 'xlsx';
import { readFileSync } from 'fs';

const wb = XLSX.read(readFileSync('c:/Users/shawo/OneDrive/Documents/HRMS Contact Information.xlsx'));
const rows = XLSX.utils.sheet_to_json(wb.Sheets['Sheet2'], { defval: '', header: 1 }).slice(1);

let currentDept = '';
const structure = {};

for (const row of rows) {
  const unit = String(row[0] || '').trim();
  const dept = String(row[1] || '').trim();
  const building = String(row[2] || '').trim();
  const floor = String(row[3] || '').trim();
  const designation = String(row[4] || '').trim();
  const role = String(row[5] || '').trim();

  if (dept) currentDept = dept;
  if (!designation) continue;

  let resolvedDept = currentDept;
  if (!resolvedDept && role.includes('-')) {
    const prefix = role.split('-')[0];
    const map = {
      Admin: 'Admin & HR',
      IT: 'IT',
      Accounts: 'Accounts & Finance',
      Audit: 'Audit',
      Commercial: 'Commercial',
      Procurement: 'Procurement',
      Merchandising: 'Merchandising',
    };
    resolvedDept = map[prefix] || prefix;
  }
  if (!resolvedDept) resolvedDept = '_unknown';

  if (!structure[resolvedDept]) {
    structure[resolvedDept] = { building: building || null, floor: floor || null, items: [] };
  }
  if (building && !structure[resolvedDept].building) structure[resolvedDept].building = building;
  if (floor && !structure[resolvedDept].floor) structure[resolvedDept].floor = floor;

  structure[resolvedDept].items.push({ designation, role });
}

for (const [dept, data] of Object.entries(structure)) {
  console.log('\n' + dept, '| building:', data.building, '| floor:', data.floor);
  data.items.forEach((i) => console.log('  -', i.designation, '=>', i.role));
}
