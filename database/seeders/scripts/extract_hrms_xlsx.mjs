import * as XLSX from 'xlsx';
import { readFileSync, writeFileSync } from 'fs';

const path = 'c:/Users/shawo/OneDrive/Documents/HRMS Contact Information.xlsx';
const wb = XLSX.read(readFileSync(path));

function sheetRows(name, headerRowIndex = 0) {
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

const employees = sheetRows('Sheet1', 2).filter((r) => r['Emp Code']);
const masters = sheetRows('Sheet2', 0).filter((r) => r['Department'] || r['Designation']);

let currentUnit = '';
for (const row of masters) {
  if (String(row['Unit']).trim()) currentUnit = String(row['Unit']).trim();
  row['Unit'] = currentUnit;
}

const hoEmployees = employees.filter((r) => String(r['Unit']).trim() === 'Head Office');
const hoMasters = masters.filter((r) => r['Unit'] === 'Head Office');

console.log('Total employees:', employees.length);
console.log('Head Office employees:', hoEmployees.length);
console.log('Total master rows:', masters.length);
console.log('Head Office master rows:', hoMasters.length);

const units = [...new Set(employees.map((r) => String(r['Unit']).trim()).filter(Boolean))];
console.log('Units in Sheet1:', units);

const depts = [...new Set(hoMasters.map((r) => r['Department']))];
const desigs = [...new Set(hoMasters.map((r) => r['Designation']))];
const buildings = [...new Set(hoMasters.map((r) => r['Building']).filter(Boolean))];
const floors = [...new Set(hoMasters.map((r) => r['Floor']).filter(Boolean))];
const roles = [...new Set(hoMasters.map((r) => r['Role']).filter(Boolean))];

console.log('HO Departments:', depts.length, depts);
console.log('HO Designations:', desigs.length);
console.log('HO Buildings:', buildings);
console.log('HO Floors:', floors);
console.log('HO Roles:', roles);

writeFileSync('database/seeders/scripts/_ho_employees.json', JSON.stringify(hoEmployees, null, 2));
writeFileSync('database/seeders/scripts/_ho_masters.json', JSON.stringify(hoMasters, null, 2));
