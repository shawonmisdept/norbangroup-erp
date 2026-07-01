import * as XLSX from 'xlsx';
import { readFileSync, writeFileSync } from 'fs';

const path = process.argv[2] || 'c:/Users/shawo/OneDrive/Documents/HRMS Contact Information.xlsx';
const wb = XLSX.read(readFileSync(path));

for (const name of wb.SheetNames) {
  const sheet = wb.Sheets[name];
  const rows = XLSX.utils.sheet_to_json(sheet, { defval: '', header: 1 });
  console.log('\n===', name, 'rows:', rows.length, '===');
  console.log('Headers:', JSON.stringify(rows[0]));
  console.log('Row2:', JSON.stringify(rows[1]));
  console.log('Row3:', JSON.stringify(rows[2]));
  writeFileSync(
    `database/seeders/scripts/_preview_${name.replace(/\s+/g, '_')}.json`,
    JSON.stringify(rows.slice(0, 5), null, 2)
  );
}
