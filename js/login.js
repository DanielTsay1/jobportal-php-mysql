let container = document.getElementById('container')

// Initialize the page with sign-in view
setTimeout(() => {
	container.classList.add('sign-in')
}, 200)

// Toggle between sign-in and sign-up
function toggle() {
	container.classList.toggle('sign-in')
	container.classList.toggle('sign-up')
}

// Form enhancement
document.addEventListener('DOMContentLoaded', function() {
	const forms = document.querySelectorAll('form')
	
	forms.forEach(form => {
		// Add input focus effects
		const inputs = form.querySelectorAll('input')
		inputs.forEach(input => {
			input.addEventListener('focus', function() {
				this.parentElement.classList.add('focused')
			})
			
			input.addEventListener('blur', function() {
				this.parentElement.classList.remove('focused')
			})
		})
		
		// Form submission enhancement
		form.addEventListener('submit', function(e) {
			const submitBtn = this.querySelector('button[type="submit"]')
			const originalText = submitBtn.textContent
			
			// Show loading state
			submitBtn.disabled = true
			submitBtn.textContent = 'Processing...'
			submitBtn.classList.add('loading')
			
			// Submit the form after a brief delay
			setTimeout(() => {
				this.submit()
			}, 1000)
		})
	})
	
	// Radio button enhancement
	const radioGroups = document.querySelectorAll('.radio-group')
	radioGroups.forEach(group => {
		const labels = group.querySelectorAll('label')
		labels.forEach(label => {
			label.addEventListener('click', function() {
				// Remove active class from all labels in this group
				labels.forEach(l => l.classList.remove('active'))
				// Add active class to clicked label
				this.classList.add('active')
			})
		})
	})
})

// Add CSS for enhanced interactions
const style = document.createElement('style')
style.textContent = `
	.radio-group label.active {
		background: var(--primary-color)
		color: var(--white)
		transform: scale(1.05)
	}
	
	.input-group.focused {
		transform: scale(1.02)
	}
	
	.loading {
		position: relative
		overflow: hidden
	}
	
	.loading::after {
		content: ''
		position: absolute
		top: 50%
		left: 50%
		width: 20px
		height: 20px
		margin: -10px 0 0 -10px
		border: 2px solid transparent
		border-top: 2px solid var(--white)
		border-radius: 50%
		animation: spin 1s linear infinite
	}
	
	@keyframes spin {
		0% { transform: rotate(0deg) }
		100% { transform: rotate(360deg) }
	}
`
document.head.appendChild(style)

const homepage =()=>{
	window.location.href="index.php";
}

function opendashboard(){
	var rec=document.getElementById('recruiter');
	var job=document.getElementById('jobseeker');
    
    if(job.checked && rec.checked){
		alert("choose any one option")}
	else if(job.checked){
		window.location.href="dashboard.html";
	}
	else if(rec.checked){
		window.location.href="recruiter.html";
	}else
	{
		alert("choose any one option"); 
	}
}
function backbutton(){
	window.location.href="dashboard-job.php";
}