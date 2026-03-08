 * {
      box-sizing: border-box;
    }

    body {
      font-family: "Segoe UI", Tahoma, Arial, sans-serif;
      margin: 0;
      background: #eaf6fb;
      color: #333;
    }

    h1 {
      text-align: center;
      margin: 20px 0;
      font-size: 32px;
      color: #8b2e2e;
    }

    /* ===== Container ===== */
    .container {
      max-width: 1200px;
      margin: auto;
      padding: 20px;
      background: linear-gradient(135deg, #a83232, #f46b45);
      border-radius: 12px;
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    /* ===== Navigation ===== */
    nav {
      flex: 1 1 240px;
      background: #fff3e6;
      border-radius: 10px;
      padding: 15px;
    }

    nav ul {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    nav ul li {
      background: #ffe2c6;
      padding: 10px;
      border-radius: 6px;
      transition: 0.3s;
    }

    nav ul li:hover {
      background: #ffb870;
      transform: translateX(-5px);
    }

    nav ul li a {
      text-decoration: none;
      color: #2b2b2b;
      font-weight: bold;
      display: block;
      text-align: center;
    }

    /* ===== Patient Info ===== */
    .info {
      flex: 2 1 450px;
      background: #f4f8f1;
      border-radius: 10px;
      padding: 20px;
      font-size: 18px;
    }

    .info p {
      margin: 10px 0;
      display: flex;
      justify-content: space-between;
      border-bottom: 1px dashed #ccc;
      padding-bottom: 6px;
    }

    .info span:first-child {
      font-weight: bold;
      color: #444;
    }

    /* ===== Visit Buttons ===== */
    .visit_type {
      width: 100%;
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 20px;
      flex-wrap: wrap;
    }

    .visit_type a {
      padding: 12px 20px;
      border-radius: 30px;
      color: #fff;
      text-decoration: none;
      font-size: 18px;
      font-weight: bold;
      transition: 0.3s;
    }

    .visit_type a:hover {
      transform: scale(1.08);
      opacity: 0.9;
    }

    #a {
      background: #6fbf73;
    }

    #b {
      background: #3fa7d6;
    }

    #c {
      background: #b3396d;
    }

    /* ===== Responsive ===== */
    @media (max-width: 992px) {
      h1 {
        font-size: 26px;
      }

      .info {
        font-size: 16px;
      }
    }

    @media (max-width: 600px) {
      .container {
        padding: 15px;
      }

      nav ul li {
        font-size: 15px;
      }

      .visit_type a {
        font-size: 16px;
        padding: 10px 14px;
      }
    }