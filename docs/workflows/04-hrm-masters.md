# HRM — Masters (এক নজরে)

**Route:** `/admin/hrm/masters`  
**Permission:** `hrm.masters.view`, `hrm.masters.manage`

---

## মূল কাজ

HR module চালানোর **প্রাথমিক setup** — factory structure, shift, holiday, leave type, biometric device register।

---

## Workflow

```
HR Admin Setup → Factory Structure → Shifts & Holidays → Device Register → Ready for Enrollment
```

---

## Sub-modules

### Organization
| Key | Label | কাজ |
|-----|-------|-----|
| `hrm-buildings` | Buildings | Factory building master |
| `hrm-floors` | Floors | Floor per building |
| `hrm-lines` | Lines / Sections | Production line per floor |

### Work Schedule
| Key | Label | কাজ |
|-----|-------|-----|
| `hrm-shifts` | Shifts | Shift timing, break, night flag |
| `hrm-holidays` | Holidays | Factory holiday calendar |

### Employee Setup
| Key | Label | কাজ |
|-----|-------|-----|
| `hrm-worker-categories` | Worker Categories | Operator, helper, QC, staff |
| `hrm-employment-types` | Employment Types | Permanent, contract, probation |
| `hrm-leave-types` | Leave Types | Casual, sick, maternity |

### Biometric
| Key | Label | কাজ |
|-----|-------|-----|
| `hrm-biometric-devices` | Biometric Devices | ZKTeco SpeedFace V5L ADMS register |

---

## 🔄 Flowchart

```
Building → Floor → Line ➔ Shift + Holiday ➔ Leave Type ➔ Device Sync Ready
```

**Pre-requisite:** Employee enrollment ও attendance process-এর আগে complete করতে হবে।
