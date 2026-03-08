 h1 {
    color: #0d9b57;
    font-family: Arial, Helvetica, sans-serif;
    margin-top: 20px;
    text-align: center;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
  }

  body {
    background: #e0f7fa;
    font-family: Arial, Helvetica, sans-serif;
  }

  .patient-info {
    background: #ffffff;
    margin: 20px auto;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2),
      0 6px 20px 0 rgba(0, 0, 0, 0.19);
    max-width: 75%;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 18px;
    color: #333333;
    display: flex;
    justify-content: space-around;
    direction: rtl;
  }

  .patient-info p {
    margin: 5px 0;

  }

  .add-va {
    background: #f5f5f5;
    margin: 20px auto;
    padding: 10px 10px 60px 10px;
    border-radius: 8px;
    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2),
      0 6px 20px 0 rgba(0, 0, 0, 0.19);
    height: 350px;
    max-width: 75%;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 20px;
    color: #333333;
  }

  .ou {
    display: flex;
    justify-content: space-between;
    height: 85%;
    overflow: hidden;
    gap: 20px;
    margin-top: 10px;
  }

  .od {
    gap: 10px;
    margin: 3px;
    padding: 8px;
    width: 50%;
  }

  .os {
    width: 50%;
    padding: 8px;
    margin: 3px;
    gap: 10px;
  }

  .od-info input,
  .os-info input {
    width: 120px;
    text-align: center;
    font-size: 18px;
    float: right;
    border-radius: 5px;
    border: 2px solid #ccc;
  }

  .od-info,
  .os-info {
    margin: 10px 0px 10px 0px;
    padding: 5px;
  }

  .os-info input,
  .os-info input:focus {
    outline: none;
    border-color: #95a0d4;
    box-shadow: 0 0 5px rgba(13, 155, 87, 0.5);
  }

  .od-info input,
  .od-info input:focus {
    outline: none;
    border-color: #95a0d4;
    box-shadow: 0 0 5px rgba(13, 155, 87, 0.5);
  }

  .od-info label,
  .os-info label {
    margin-right: 10px;
    font-size: 18px;
  }

  h3 {
    background: #0d9b57;
    color: white;
    padding: 8px;
    border-radius: 4px;
    text-align: center;
    margin: 0px 0px 10px 0px;
    font-size: 22px;
  }

  #submit_bt {
    position: relative;
    width: 100px;
    height: 40px;
    background: #0d9b57;
    color: white;
    cursor: pointer;
    border: none;
    font-size: 18px;
    border-radius: 8px;
    margin-top: 15px;
    float: right;
  }