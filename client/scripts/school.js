export function initSchoolOptions(ticket) {
  const schoolButtons = ticket.querySelectorAll('.schoolOption');
  const hiddenInput = ticket.querySelector('input[id^="schoolInput-"]');

  if (!hiddenInput || schoolButtons.length === 0) return;

  hiddenInput.value = schoolButtons[0].dataset.school;
  schoolButtons[0].classList.add('active');

  schoolButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      schoolButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      hiddenInput.value = btn.dataset.school;
    });
  });
}