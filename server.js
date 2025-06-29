const express = require('express');
const cors = require('cors');
const { execSync } = require('child_process');
const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());
app.use(express.static('public'));

app.post('/api', (req, res) => {
  try {
    const params = JSON.stringify(req.body.pow);
    const result = execSync(`node pow_calculator.js '${params}'`, {
      timeout: 60000
    });
    res.json(JSON.parse(result));
  } catch (error) {
    res.status(500).json({
      error: 'POW computation failed',
      details: error.message
    });
  }
});

app.listen(PORT, () => {
  console.log(`🚀 Server running on port ${PORT}`);
});
