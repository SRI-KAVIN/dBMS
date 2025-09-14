const express = require("express");
const mysql = require("mysql2");

const app = express();
app.use(express.json());

// MySQL Connection
const db = mysql.createConnection({
  host: "localhost",
  user: "root",     // change if needed
  password: "",     // set your MySQL password
  database: "university"
});

db.connect(err => {
  if (err) {
    console.error("Error connecting to DB:", err);
    return;
  }
  console.log("✅ Connected to MySQL database");
});

// Routes

// Add student
app.post("/students", (req, res) => {
  const { name, age, department } = req.body;
  db.query(
    "INSERT INTO students (name, age, department) VALUES (?, ?, ?)",
    [name, age, department],
    (err, result) => {
      if (err) return res.status(500).send(err);
      res.send("Student added successfully!");
    }
  );
});

// Get all students
app.get("/students", (req, res) => {
  db.query("SELECT * FROM students", (err, results) => {
    if (err) return res.status(500).send(err);
    res.json(results);
  });
});

// Update student
app.put("/students/:id", (req, res) => {
  const { id } = req.params;
  const { name, age, department } = req.body;
  db.query(
    "UPDATE students SET name=?, age=?, department=? WHERE id=?",
    [name, age, department, id],
    (err, result) => {
      if (err) return res.status(500).send(err);
      res.send("Student updated successfully!");
    }
  );
});

// Delete student
app.delete("/students/:id", (req, res) => {
  const { id } = req.params;
  db.query("DELETE FROM students WHERE id=?", [id], (err, result) => {
    if (err) return res.status(500).send(err);
    res.send("Student deleted successfully!");
  });
});

// Start server
app.listen(3000, () => {
  console.log("🚀 Server running on http://localhost:3000");
});

