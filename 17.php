 body {
      font-family: "Segoe UI", Tahoma, Arial, sans-serif;
      margin: 0;
      background: #f5f7fa;
      color: #333;
    }

    h1 {
      font-size: 32px;
      text-align: center;
      color: #b24433;
      margin: 20px 0;
    }

    /* Sidebar Links */
    .sidenav {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 10px;
      margin: 10px;
    }

    .sidenav a {
      display: inline-block;
      background: #d7c588;
      padding: 8px 15px;
      border-radius: 8px;
      color: #4314a3;
      text-decoration: none;
      font-weight: bold;
      transition: 0.3s;
      box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
    }

    .sidenav a:hover {
      background: #c1b06a;
      transform: scale(1.05);
    }

    /* Search Bar */
    .search-bar {
      background: #c68f8f;
      margin: 20px auto;
      padding: 15px;
      border-radius: 10px;
      width: 80%;
      max-width: 800px;
      text-align: center;
      box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
    }

    .search-bar input {
      width: 60%;
      padding: 8px 10px;
      font-size: 16px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    /* Table */
    .table-scroll {
      max-height: 60vh;
      overflow-y: auto;
      margin: 10px auto 40px;
      width: 95%;
      border-radius: 10px;
      box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
      background: #fff;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      padding: 10px;
      text-align: center;
      font-size: 16px;
    }

    th {
      background: #6c7ae0;
      color: #fff;
      font-weight: bold;
      position: sticky;
      top: 0;
      z-index: 1;
    }

    tr:nth-child(even) {
      background: #f9f9f9;
    }

    tr:hover {
      background: #e3f2fd;
      transform: scale(1.01);
      transition: 0.2s;
    }

    /* Buttons */
    .open-btn {
      background-color: #27ae60;
      color: #fff;
      padding: 6px 12px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      transition: 0.3s;
    }

    .open-btn:hover {
      background-color: #1e8449;
      transform: scale(1.05);
    }

    .delete-btn {
      background-color: #e74c3c;
      color: #fff;
      padding: 6px 12px;
      width: auto;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }

    .delete-btn:hover {
      background-color: #c0392b;
      transform: scale(1.05);
    }

    /* Responsive Table on Small Screens */
    @media (max-width: 1280px) {
      th,
      td {
        font-size: 15px;
        padding: 9px;
      }
     

    }
    @media (max-width: 1024px) {
      th,
      td {
        font-size: 14px;
        padding: 8px;
      }

      .search-bar input {
        width: 80%;
        font-size: 14px;
      }

    }
    @media (max-width: 768px) {

      table,
      thead,
      tbody,
      th,
      td,
      tr {
        display: block;
      }

      thead tr {
        display: none;
      }

      tr {
        margin: 10px 0;
        border: 1px solid #ccc;
        border-radius: 10px;
        padding: 10px;
      }

      td {
        text-align: right;
        padding: 8px;
        border: none;
        position: relative;
      }

      td::before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        font-weight: bold;
      }

      .table-scroll {
        max-height: none;
      }
    }



    .search-bar {
  background: var(--card);
  margin: 25px auto;
  padding: 18px;
  border-radius: var(--radius);
  width: 80%;
  max-width: 850px;
  text-align: center;
  box-shadow: var(--shadow);
}

.search-bar input {
  width: 65%;
  padding: 12px 14px;
  font-size: 16px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  transition: 0.3s;
}

.search-bar input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
}
