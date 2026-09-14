const tl = gsap.timeline();

tl.from(".display-container", {
  opacity: 0,
  y: 30,
  duration: 0.6,
  stagger: 0.1,
  ease: "power2.out"
});
