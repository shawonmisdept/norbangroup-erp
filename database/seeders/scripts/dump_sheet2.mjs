import * as XLSX from 'xlsx';
import { readFileSync, writeFileSync } from 'fs';

const wb = XLSX.read(readFileSync('c:/Users/shawo/OneDrive/Documents/HRMS Contact Information.xlsx'));
const rows = XLSX.utils.sheet_to_json(wb.Sheets['Sheet2'], { defval: '', header: 1 });
writeFileSync('database/seeders/scripts/_sheet2_raw.json', JSON.stringify(rows.slice(0, 40), null, 2));
console.log('rows', rows.length);
rows.slice(0, 15).forEach((r, i) => console.log(i, JSON.stringify(r)));
