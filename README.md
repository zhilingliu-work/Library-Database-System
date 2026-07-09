# 👋 Hi there, 我是劉志凌 (Zhiling Liu)

<p align="center">
  <img src="https://img.shields.io/badge/Education-高科大%20電腦與通訊工程系-blue?style=for-the-badge&logo=education" alt="Education">
  <img src="https://img.shields.io/badge/Location-台灣%20高雄-0078d4?style=for-the-badge&logo=googlemaps" alt="Location">
</p>

## 🚀 關於我 (About Me)
目前就讀於**國立高雄科技大學電腦與通訊工程系**。我具備跨領域學習背景，專注於**機器學習應用**、**軟體系統分析與規劃**、**後端關聯式資料庫實作**，以及**啟發式最佳化演算法**的開發。

實務開發上，我擅長運用生成式 AI 輔助建立高效率的程式架構與邏輯測試，同時具備良好的軟體工程思維與專案風險控管觀念，重視系統穩定度。目前正在尋找**軟體工程實習機會**，期待能在實務環境中與團隊協作，持續精進技術量能！

---

## 🛠️ 技術棧 (Tech Stack)

### 💻 程式語言 & 前端基礎
<p>
  <img src="https://img.shields.io/badge/Python-3776AB?style=flat-square&logo=python&logoColor=white" alt="Python">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/C-A8B9CC?style=flat-square&logo=c&logoColor=black" alt="C">
  <img src="https://img.shields.io/badge/C%2B%2B-00599C?style=flat-square&logo=cplusplus&logoColor=white" alt="C++">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=flat-square&logo=html5&logoColor=white" alt="HTML5">
</p>

### 🔧 開發工具 & 資料庫
<p>
  <img src="https://img.shields.io/badge/VS%20Code-007ACC?style=flat-square&logo=visual-studio-code&logoColor=white" alt="VS Code">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Android%20Studio-3DDC84?style=flat-square&logo=android-studio&logoColor=white" alt="Android Studio">
</p>

### 🧠 核心技術經歷
* **關聯式資料庫工程：** 3NF 實體關係正規化設計、跨表 JOIN 優化，以及防禦 SQL 注入之參數化查詢實作。
* **機器學習與資料管線：** Pandas、NumPy 資料預處理管線設計，與 scikit-learn 隨機森林模型調優。
* **演算法核心實作：** 遺傳演算法 (GA) 解空間編碼、動態突變機制優化與收斂行為分析。
* **系統分析與逆向工程：** 物件導向系統分析 (OOAD) 與 UML 模型建構。

---

## 📁 精選專案作品 (Featured Projects)

### 📚 [線上圖書借閱系統 —— Web 關聯式資料庫管理與實作](https://github.com/zhilingliu-work/Library-Database-System)
* **專案概述：** 以原生 PHP 與 MySQL 從零建構之線上圖書管理系統，專注於關聯式資料庫底層設計、交易完整性與應用層資安防禦。
* **技術亮點：** 嚴格落實 **3NF 資料庫正規化**與外鍵完整性約束（Foreign Key Constraints）杜絕資料異常；實作基於角色的雙軌權限分流 (RBAC) 與跨表查詢優化；全面導入 **Session 狀態驗證**與**參數化查詢 (Parameterized Queries)**，達成免疫 SQL 注入攻擊之資安標準。

### 🧬 [任務排程最佳化研究 —— 遺傳演算法 (GA) 核心機制與應用](https://github.com/zhilingliu-work/Genetic-Algorithm-Research)
* **專案概述：** 針對平行運算系統中屬於 NP-hard 的 DAG 任務排程問題，利用 Python 實作啟發式最佳化求解框架。
* **技術亮點：** 設計 `(ss, ms)` 雙元件染色體編碼以完美符合拓撲排序限制；導入**三階段動態突變率**（0.27 ➔ 0.12 ➔ 0.02）優化全域搜尋，並加入「連續 5 代未改善則強制突變」之**防陷落機制**，結合 $60\%$ 的**激進精英保留策略**，顯著提升演算法收斂效率與求解品質。

### 📊 [PAMA 智慧會議助理 —— 逆向工程與 OOAD 系統分析](https://github.com/zhilingliu-work/OOSA-Case-Study-PAMA)
* **專案概述：** 以獲獎智慧語音協作系統為標竿進行逆向工程，推導出標準化軟體工程規劃書。
* **核心成果：** 釐清並規劃 17 項核心事件與例外錯誤應變路徑，建構 9 大核心類別之 UML 靜態與動態行為模型（活動圖），並完成具備高一致性、消除冗餘的 3NF 資料庫正規化架構設計。

### 🏥 [基於隨機森林之手術時間預測與資料管線實作](https://github.com/zhilingliu-work/Medical-Surgery-Time-Prediction)
* **專案概述：** 利用 Python 資料科學技術預估臨床手術耗時，用以解決醫療現場排程失準與醫護過勞之痛點。
* **核心成果：** 清洗並提取 11 項核心臨床特徵建立自動化資料管線，針對 5 大科別實施客製化隨機森林超參數調優（包含動態深度控制與過擬合修正），模型平均絕對誤差（MAE）表現優異，全數通過臨床效能考核。

---

## ✉️ 聯絡資訊 (Contact)
* **Email:** zhilingliu9304@gmail.com
* **電話:** 0905-384-265
