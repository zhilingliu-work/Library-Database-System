# 📁 Online Book Borrowing System
## Web 線上圖書借閱與輕量化後端資料庫管理系統

<p align="center">
  <img src="https://img.shields.io/badge/Database-MySQL_/_PHP-blue?style=for-the-badge&logo=mysql&logoColor=white" alt="DB">
  <img src="https://img.shields.io/badge/Architecture-3NF%20Compliant-orange?style=for-the-badge" alt="Architecture">
  <img src="https://img.shields.io/badge/Security-SQL%20Injection%20Protected-red?style=for-the-badge" alt="Security">
</p>

## 📌 專案與作者資訊

| 項目 | 詳細資訊 |
| :--- | :--- |
| **課程名稱** | 資料庫系統 (Database Systems) |
| **授課教師** | 徐偉智 教授 |
| **團隊成員** | 國立高雄科技大學 電腦與通訊工程系<br>C112110130 劉志凌 <br>C112110139 陳保均 <br>C112110140 王建宇  |
| **製作日期** | 民國 115 年 6 月 22 日  |

---

## 📖 專案簡介與設計核心

本系統為輕量化 Web 線上圖書借閱系統，專注於後端關聯式資料庫的 CRUD 邏輯實作，不刻意追求華麗視覺 。主要技術特色如下：
* **核心功能**：具備即時庫存控管與動態借還書紀錄 。
* **權限分流**：區分「一般使用者(學生)」與「管理者」兩種角色 。
* **資訊安全**：導入 Session 驗證防護與參數化查詢（防範 SQL 注入） 。
* **實務應用**：確保系統在學校環境下，具備資料庫快速備份與還原的穩定性 。

---

## 📁 儲存庫檔案樹狀圖 (Repository Structure)

```markdown
my_library_system/
├── config/                # 【系統配置區】
│   └── db.php             # 資料庫連線核心設定
│
├── public/                # 【靜態資源預留區】
│   ├── css/               # 存放系統介面樣式表 (.css)（目前留空供後續擴展）
│   └── js/                # 存放前端互動邏輯 (.js)（目前留空供後續擴展）
│
├── admin.php              # 管理後台：圖書庫存全功能 CRUD 維護
├── index.php              # 借書大廳：學生首頁、即時館藏瀏覽與批次借閱
├── login.php              # 資安門神：身分驗證與參數化查詢攔截防禦
├── logout.php             # 安全登出：主動銷毀伺服器端 Session 憑證
├── manage_users.php       # 帳號管理：管理者專屬之學生帳號 CRUD 後台
└── mybooks.php            # 個人專區：跨表 Join 查詢目前借閱與還書引擎
