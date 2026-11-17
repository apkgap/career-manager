# Career & Job Vacancies Manager (`career-manager`)

ปลั๊กอินระบบ **ร่วมงานกับเรา / ประกาศรับสมัครงาน (Career & Job Opportunities)** แบบ **Standalone 100%** สำหรับ WordPress

---

## ตัวอย่างการแสดงผล (UI Preview)

<div align="center">
  <img width="100%" alt="Desktop Job Board Preview" src="https://github.com/user-attachments/assets/8c757181-ba32-471f-9687-7d858121f2df" />
</div>

| รายการประกาศงานบนมือถือ (Mobile View) | หน้าต่างตัวกรองแบบ Popup (Filter Modal) |
| :---: | :---: |
| <img width="360" alt="Mobile Job Cards View" src="https://github.com/user-attachments/assets/78caae98-2ae1-4b5c-8c58-c215c068516e" /> | <img width="360" alt="Mobile Filter Popup Modal" src="https://github.com/user-attachments/assets/0a7c396b-4202-4a8d-98fc-37951dfa35ca" /> |

---

## โครงสร้างระบบ (Architecture & Sitemap)

<div align="center">
  <img width="100%" alt="Career Manager Architecture & Sitemap" src="./assets/images/career-sitemap.jpg" />
</div>

---

## การติดตั้งและการใช้งาน

1. วางโฟลเดอร์ `career-manager` ที่ `wp-content/plugins/career-manager` แล้วกด **Activate**
2. ตั้งค่าสีและข้อความได้ที่เมนู **ประกาศรับสมัครงาน → ตั้งค่า & Shortcodes**

### ช่องทางการแสดงผล
- **Shortcode หน้าบอร์ดสมัครงาน**: `[career_tabs title="ประกาศสมัครงาน" posts_per_page="10"]`
- **Shortcode กล่องไฟล์แนบ**: `[career_download_file]`
- **Elementor Widget**: `Career Job Board (ร่วมงานกับเรา)`
- **Archive URL**: `/career/`
