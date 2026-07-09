# 📁 Online Book Borrowing System
## 輕量化 Web 線上圖書借閱與後端關聯式資料庫管理系統

<p align="center">
  <img src="https://img.shields.io/badge/Database-MySQL_8.0-blue?style=for-the-badge&logo=mysql&logoColor=white" alt="DB">
  <img src="https://img.shields.io/badge/Language-PHP_8.x-purple?style=for-the-badge&logo=php&logoColor=white" alt="Language">
  <img src="https://img.shields.io/badge/Environment-AppServ_Architecture-FFE162?style=for-the-badge" alt="Env">
  <img src="https://img.shields.io/badge/Architecture-3NF%20Compliant-orange?style=for-the-badge" alt="Architecture">
  <img src="https://img.shields.io/badge/Security-SQL_Injection_Protected-red?style=for-the-badge" alt="Security">
</p>

## 📌 專案與作者資訊

| 項目 | 詳細資訊 |
| :--- | :--- |
| **課程名稱** | 資料庫系統 (Database Systems) |
| **授課教師** | 徐偉智 教授 |
| **團隊成員** | 國立高雄科技大學 電腦與通訊工程系<br>C112110130 劉志凌<br>C112110139 陳保均<br>C112110140 王建宇 |
| **製作日期** | 民國 115 年 6 月 10 日 |
| **開發環境** | 本地端 AppServ 整合式網頁伺服器環境 (Apache + PHP + MySQL) |

---

## 📖 專案背景與設計思維

本專案為一套專注於**後端關聯式資料庫（RDBMS）CRUD 邏輯與交易完整性**實作的輕量化 Web 線上圖書借閱系統。本系統採取務實主義工程導向，不刻意追求前端視覺的華麗裝飾，而是將核心研發精力完全聚焦於關聯式資料庫的底層建置、資料一致性維護、多表查詢效能優化以及應用層資安主動防禦。

### 🌟 系統四大工程亮點
1. **即時高可用庫存控管**：動態連動借還書紀錄，在多用戶並行操作下仍能保證資料庫數值的絕對精確。
2. **基於角色的權限分流 (RBAC)**：實作完備的雙角色存取控制，嚴格隔離一般學生與系統管理者的操作邊界。
3. **應用層與資料庫層雙重防護**：全面導入安全 Session 憑證查核機制，並強制採取參數化查詢以防禦惡意 SQL 注入。
4. **災難復原與運作穩定性**：針對學校教學與實際運行環境，優化了關聯式資料庫的快速備份與還原機制。

---

## 🛠️ 系統架構與軟體工程設計

本專案遵循傳統的三層式架構 (3-Tier Architecture) 設計思維，將系統拆解為三個獨立且互相解耦的層級，確保程式碼的可維護性：

    [ 展示層 (Presentation Layer) ]  <--> 網頁瀏覽器 (HTML5 / Bootstrap CSS / Vanilla JS)
                   ↓↑
    [ 業務邏輯層 (Business Logic) ]  <--> PHP 伺服器端核心模組 (Session 阻擋、商業邏輯判定)
                   ↓↑
    [ 資料存取層 (Data Access Layer) ] <--> MySQL 關聯式資料庫 (SQL 指令、外鍵完整性約束)

* **展示層**：由 public/ 目錄下的靜態資源與各 PHP 檔案的前端 HTML 組成，負責渲染動態資料表格並捕捉使用者事件。
* **業務邏輯層**：由核心 PHP 程式碼構成，負責處理由展示層傳入的請求，進行身分驗證、權限檢查、防呆攔截，並決定資料庫的交易走向。
* **資料存取層**：由 config/db.php 建立持久化連線，透過底層 SQL 指令與 MySQL 內核直接互動，確保每一次操作都符合實體完整性。

---

## 🔍 資料庫實體關係設計與正規化剖析 (Schema Design)

為了徹底根除關聯式資料庫在頻繁更新時可能產生的插入、更新與刪除異常，本系統之資料表結構嚴格落實了 **第三正規化 (3NF)** 設計：

### 1. 資料表欄位定義 (Schema Specifications)

#### 📝 使用者表 (user)
負責全系統的帳號資安認證、密碼比對與角色存取分流。
* username (VARCHAR, PK)：主鍵，使用者的唯一登入憑證（學號）。
* password (VARCHAR)：存放安全比對之密碼欄位。
* role (VARCHAR)：角色欄位，限定存入 student 或 admin，作為權限路由分流依據。

#### 📝 圖書表 (book)
負責存放圖書館內所有的書目資產配置與架上即時庫存。
* book_id (INT, PK, Auto_Increment)：主鍵，書籍唯一流水編號。
* title (VARCHAR)：書籍之中英文完整名稱。
* author (VARCHAR)：書籍作者姓名。
* quantity (INT)：目前架上剩餘可借出的實體庫存數量。

#### 📝 借閱紀錄表 (borrow_record)
作為一張交織關聯資料表（Bridge Table），成功將 user 與 book 的多對多關係拆解為安全的一對多關係。
* record_id (INT, PK, Auto_Increment)：主鍵，紀錄唯一識別流水號。
* username (VARCHAR, FK)：外鍵，參考至 user(username)，記錄借閱者身分。
* book_id (INT, FK)：外鍵，參考至 book(book_id)，精確捕捉被借閱之書目。
* borrow_date (DATE)：借閱事務發生時的資料庫系統時間戳記。

#### 📝 管理者表 (admin)
獨立之高權限管理表，確保系統營運維護之安全性。
* username (VARCHAR, PK) / password (VARCHAR) / role (VARCHAR)。

---

### 2. 3NF 正規化推論說明

* **第一正規化 (1NF)**：所有資料表內的所有欄位皆為不可再分割的單一原子值，無重複群或多值屬性存在。
* **第二正規化 (2NF)**：在多對多關聯的 borrow_record 中，引進獨立的代理主鍵 record_id，確保所有非主鍵欄位都完全相依於整體主鍵，消除部分相依性。
* **第三正規化 (3NF)**：在 book 資料表中，所有非主鍵欄位皆直接相依於 book_id 主鍵，欄位與欄位之間不存在任何傳遞相依關係，最大程度降低資料冗餘度。

---

## ⚡ 核心使用案例與業務邏輯深度解析 (Use Case Specifications)

### 🔹 系統登入與防禦性會話控制流 (login.php & logout.php)
* **安全性分流**：系統接收用戶提交的帳密後，自 user 表執行參數化檢索。比對無誤後建立 Session 憑證，並依據 role 欄位進行硬性分流：student 引導至借書大廳；admin 引導至後台控制台。
* **會話防護攔截 (Session Guard)**：在全系統內部網頁頂端皆封裝了防護閘道。若有惡意訪客試圖透過輸入網址直接存取內部頁面，系統會立刻偵測到 Session 內缺乏合法憑證，進而強制重定向回 login.php。
* **安全銷毀機制**：當用戶點擊「登出」按鈕時，系統執行安全退出，徹底抹除伺服器會話緩存與用戶端憑證。

### 🔹 線上借閱圖書與交易控制 (index.php)
當一般學生在借書大廳執行「線上借閱」時，後端執行以下標準的事務校驗流程：
1. **即時讀取**：向 book 表發出檢索，確認目標圖書的 quantity 數值。
2. **條件式分支（防呆攔截）**：
   * **[基本路徑 - 庫存充足]**：若庫存大於 0，系統立刻寫入一筆新紀錄至 borrow_record，並同時同步更新 book 表將該書籍的數量扣減 1 冊。
   * **[擴充路徑 - 庫存耗盡]**：若偵測到庫存已為 0，後端直接發出中斷命令，並對前端拋出庫存不足的例外提示警告。
   * **[重複借閱攔截]**：若資料庫內已存在該學號對該書目「借閱中且尚未歸還」的關聯紀錄，系統亦會拒絕本次操作，防止單一學生重複借閱。
3. **批次處理優化**：前端實作了「一鍵批次勾選借閱」機制。使用者可一次勾選多本書籍，後端在接收到陣列資料後進行批次事務處理，大幅降低了與資料庫連線的網路開銷。

### 🔹 個人借閱專區與多表關聯優化 (mybooks.php)
為了動態展現個別學生當前的未還書籍清單，系統採用高效的內連接 (INNER JOIN) 資料庫核心查詢語法：

    SELECT b.book_id, b.title, b.author, br.borrow_date 
    FROM borrow_record br
    INNER JOIN book b ON br.book_id = b.book_id
    WHERE br.username = :current_session_username;

* **運作機制**：將 borrow_record 與 book 透過外鍵 book_id 在記憶體中進行高效黏合，單次掃描即可精準拉出該學生借閱的所有書名、作者與借閱日期，大幅減輕伺服器 CPU 的檢索負擔。
* **歸還連動機制**：當學生點擊「還書」按鈕時，後端發出刪除指令清除該筆綁定，並在同一事務內執行更新讓庫存加一，讓前端網頁刷新時，館藏庫存與借閱列表達到 100% 的即時同步化。

### 🔹 管理者權限與實體約束防護 (admin.php & manage_users.php)
系統管理者擁有對資料庫實體進行完整 CRUD 操縱的最高權限：
* **圖書與帳號全功能 CRUD**：管理者可在後台直接發動新增、更新與刪除指令變更書籍存量或學生帳號資料。
* **外鍵完整性防禦機制**：當管理者試圖在後台刪除某一個學生帳號時，如果該帳號在 borrow_record 中尚有未完成的借閱對應資料，MySQL 底層的外鍵約束將會自動觸發攔截防禦，拒絕該次刪除操作，杜絕孤兒紀錄產生的悲劇。

---

## 🔒 安全防護與參數化查詢細節說明

### 🛑 徹底杜絕 SQL 注入攻擊 (Anti-SQL Injection)
本系統全面廢除字串拼接，在所有涉及使用者輸入的欄位（包含登入框、模糊搜尋框、CRUD 輸入表單），一律採取 **參數化查詢 (Parameterized Queries / Prepared Statements)** 機制：
1. **先行編譯**：後端將 SQL 指令骨架先行送往 MySQL 資料庫進行語法樹編譯與路徑規劃。
2. **安全綁定**：編譯完成後，才將使用者輸入的純文字字串當作純資料參數安全綁定進去。
3. **優勢成效**：這確保了不論使用者輸入多麼詭異的惡意代碼，MySQL 都只會將其視為平凡的字串欄位資料處理，而絕對不會將其當成 SQL 指令來執行，從根源上將 SQL Injection 的風險降至絕對零度。

---

## 📈 系統功能介面成果展示 (UI / UX Highlights)

### 1. 安全入口登入端
* 實作了防呆且極簡的身分驗證介面，支援不同角色的安全登入，錯誤時能給予精確的提示阻斷。

### 2. 智慧化線上借書大廳
* 動態從資料庫撈取即時館藏，對於庫存量為 0 的書籍，系統會自動在前端將操作按鈕置換為不可點擊的「已無庫存」灰色狀態。
* 完美整合單獨借閱與多選批次一鍵借閱，操作結果皆能即時在網頁頂端彈出清晰的動態狀態回饋。

### 3. 個人借閱與動態歸還控制台
* 清晰羅列當前登入學號名下的所有未還書籍。歸還操作一鍵完成後，畫面會動態刷新並告知成功歸還書籍，原紀錄瞬間隱去，展現極高的流暢度。

---

## 📁 儲存庫檔案樹狀圖 (Repository Structure)

    my_library_system/
    ├── config/                # 【資料庫連接配置區】
    │   └── db.php             # 資料庫持久化連線設定與 PDO/mysqli 連線控制核心
    │
    ├── public/                # 【前端靜態資源預留區】
    │   ├── css/               # 存放全系統介面樣式表 (.css)，維護整體視覺規範
    │   └── js/                # 存放前端擴展互動與 AJAX 異步請求邏輯 (.js)
    │
    ├── admin.php              # 管理後台：提供圖書資產全功能 CRUD 維護與後台儀表板
    ├── index.php              # 借書大廳：學生專屬首頁，支援即時館藏檢索與批次勾選借閱
    ├── login.php              # 資安門神：身分驗證控制層，實作參數化查詢攔截防禦
    ├── logout.php             # 安全登出：主動銷毀伺服器端 Session 憑證，防止會話劫持
    ├── manage_users.php       # 帳號管理：管理者專屬之學生帳號 CRUD 後台與權限變更
    └── mybooks.php            # 個人專區：跨表 Join 查詢目前借閱，整合還書資料庫連動引擎
