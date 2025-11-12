// 高考倒计时组件（可复用）
// 使用：在页面中加入 #gaokao-countdown 结构，并引入本脚本。
(function() {
  function getNextExamDate(now) {
    // 每年 6 月 7 日 09:00
    const year = now.getFullYear();
    let exam = new Date(year, 5, 7, 9, 0, 0); // 月份从0开始，5代表6月
    if (now.getTime() > exam.getTime()) {
      exam = new Date(year + 1, 5, 7, 9, 0, 0);
    }
    return exam;
  }

  function formatCountdown(diffMs) {
    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diffMs / (1000 * 60 * 60)) % 24);
    const mins = Math.floor((diffMs / (1000 * 60)) % 60);
    const secs = Math.floor((diffMs / 1000) % 60);
    return `${days} 天 ${hours} 时 ${mins} 分 ${secs} 秒`;
  }

  function updateTitleYear(examDate) {
    const titleEl = document.getElementById('countdown-title');
    if (titleEl) {
      titleEl.textContent = `${examDate.getFullYear()}年高考倒计时`;
    }
  }

  function updateCountdown() {
    const timerEl = document.getElementById('countdown-timer');
    if (!timerEl) return;
    const now = new Date();
    const examDate = getNextExamDate(now);
    updateTitleYear(examDate);
    const diff = examDate.getTime() - now.getTime();
    if (diff <= 0) {
      timerEl.innerHTML = '高考进行中，加油！';
      return;
    }
    timerEl.innerHTML = formatCountdown(diff);
  }

  // 初始化与实时刷新
  document.addEventListener('DOMContentLoaded', function() {
    updateCountdown();
    setInterval(updateCountdown, 1000);
  });
})();