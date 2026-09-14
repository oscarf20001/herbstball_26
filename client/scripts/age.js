export function initAgeOptions(ticket) {
  const ageButtons = ticket.querySelectorAll('.ageOption');
  const hiddenInput = ticket.querySelector('input[id^="ageInput-"]');

  if (!hiddenInput || ageButtons.length === 0) return;

  // Default setzen
  hiddenInput.value = ageButtons[0].dataset.age;
  ageButtons[0].classList.add('active');

  ageButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      ageButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      hiddenInput.value = btn.dataset.age;
    });
  });
}